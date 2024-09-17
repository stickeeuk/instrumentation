<?php

namespace Stickee\Instrumentation\Laravel\Providers;

use Illuminate\Support\ServiceProvider;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use OpenTelemetry\API\Common\Time\Clock;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Instrumentation\Configurator;
use OpenTelemetry\API\LoggerHolder;
use OpenTelemetry\Contrib\Logs\Monolog\Handler;
use OpenTelemetry\Contrib\Otlp\LogsExporter;
use OpenTelemetry\Contrib\Otlp\MetricExporter;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Common\Export\TransportInterface;
use OpenTelemetry\SDK\Logs\EventLoggerProvider;
use OpenTelemetry\SDK\Logs\EventLoggerProviderInterface;
use OpenTelemetry\SDK\Logs\LoggerProvider;
use OpenTelemetry\SDK\Logs\LoggerProviderInterface;
use OpenTelemetry\SDK\Logs\Processor\BatchLogRecordProcessor;
use OpenTelemetry\SDK\Metrics\Data\Temporality;
use OpenTelemetry\SDK\Metrics\MeterProvider;
use OpenTelemetry\SDK\Metrics\MeterProviderInterface;
use OpenTelemetry\SDK\Metrics\MetricReader\ExportingReader;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOnSampler;
use OpenTelemetry\SDK\Trace\Sampler\TraceIdRatioBasedSampler;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SDK\Trace\TracerProviderInterface;
use OpenTelemetry\SemConv\ResourceAttributes;
use Stickee\Instrumentation\Exporters\Events\OpenTelemetry;
use Stickee\Instrumentation\Laravel\Config;

/**
 * Open Telemetry service provider
 */
class OpenTelemetryServiceProvider extends ServiceProvider
{
    /**
     * The config
     *
     * @var \Stickee\Instrumentation\Laravel\Config $config
     */
    private Config $config;

    /**
     * Register the service provider
     */
    public function register(): void
    {
        $this->config = $this->app->make(Config::class);

        // Handler for sending `Log::...` calls to the OpenTelemetry collector
        $this->app->bindIf(Handler::class, function () {
            return new Handler(Globals::loggerProvider(), 'info', true);
        });

        $this->app->bind(EventLoggerProviderInterface::class, fn () => Globals::eventLoggerProvider());
        $this->app->bind(MeterProviderInterface::class, fn () => Globals::meterProvider());
    }

    private function getTracerProvider(): TracerProviderInterface
    {
        $sampler = $this->config->traceSampleRate() == 1
            ? new AlwaysOnSampler()
            : new TraceIdRatioBasedSampler($this->config->traceSampleRate());

        $resourceInfo = ResourceInfo::create(Attributes::create([
            ResourceAttributes::SERVICE_NAME => config('app.name', 'laravel'),
            ResourceAttributes::DEPLOYMENT_ENVIRONMENT_NAME => config('app.env', 'production'),
        ]));

        $exporter = new SpanExporter($this->getOtlpTransport('/v1/traces', 'application/x-protobuf'));
        $processor = BatchSpanProcessor::builder($exporter)->build();

        register_shutdown_function(fn () => $processor->shutdown());

        return TracerProvider::builder()
            ->setSampler($sampler)
            ->setResource($resourceInfo->merge($resourceInfo, ResourceInfoFactory::defaultResource()))
            ->addSpanProcessor($processor)
            ->build();
    }

    private function getMeterProvider(): MeterProviderInterface
    {
        $exporter = new MetricExporter($this->getOtlpTransport('/v1/metrics'), Temporality::CUMULATIVE);
        $reader = new ExportingReader($exporter);

        register_shutdown_function(fn () => $reader->shutdown());

        return MeterProvider::builder()
            ->addReader($reader)
            ->build();
    }

    private function getLoggerProvider(): LoggerProviderInterface
    {
        $exporter = new LogsExporter($this->getOtlpTransport('/v1/logs'));
        $processor = (new BatchLogRecordProcessor($exporter, Clock::getDefault()));

        register_shutdown_function(fn () => $processor->shutdown());

        return LoggerProvider::builder()
            ->addLogRecordProcessor($processor)
            ->build();
    }

    /**
     * Get an OTLP transport
     *
     * @param string $path The path to append to the DSN
     * @param string $contentType The content type
     *
     * @return \OpenTelemetry\SDK\Common\Export\TransportInterface
     */
    private function getOtlpTransport(string $path, $contentType = 'application/json'): TransportInterface
    {
        dump(app(OtlpHttpTransportFactory::class));
        return (app(OtlpHttpTransportFactory::class))
            ->create($this->config->openTelemetry('dsn') . $path, $contentType, [], null, 1, 100, 1);
    }

    /**
     * Bootstrap any application services
     */
    public function boot(): void
    {
        static $booted = false;

        if (!$booted) {
            dump('booting OpenTelemetryServiceProvider');
            // Setting a Logger on the LoggerHolder means that if the OpenTelemetry Collector
            // is not available, the logs will still be sent to stderr instead of throwing an exception
            LoggerHolder::set(new Logger('otel', [new StreamHandler('php://stderr')]));

            $loggerProvider = $this->getLoggerProvider();

            $configurator = Configurator::create()
                ->withTracerProvider($this->getTracerProvider())
                ->withMeterProvider($this->getMeterProvider())
                ->withLoggerProvider($loggerProvider)
                ->withEventLoggerProvider(new EventLoggerProvider($loggerProvider));

            $scope = $configurator->activate();

            register_shutdown_function(fn () => $scope->detach());
            // $booted = true;
        }
    }
}
