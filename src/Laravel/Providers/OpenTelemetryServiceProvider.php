<?php

namespace Stickee\Instrumentation\Laravel\Providers;

use Exception;
use Illuminate\Support\ServiceProvider;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use OpenTelemetry\API\Common\Time\Clock;
use OpenTelemetry\API\LoggerHolder;
use OpenTelemetry\Contrib\Logs\Monolog\Handler;
use OpenTelemetry\Contrib\Otlp\LogsExporter;
use OpenTelemetry\Contrib\Otlp\MetricExporter;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Common\Export\TransportInterface;
use OpenTelemetry\SDK\Logs\EventLogger;
use OpenTelemetry\SDK\Logs\LoggerProvider;
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
use Stickee\Instrumentation\Utils\OpenTelemetryConfig;

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

        if (!$this->openTelemetryIsInstalled()) {
            $this->app->bind(OpenTelemetryConfig::class, function () {
                throw new Exception('OpenTelemetry client library not installed, please run composer require - see README.md for packages required');
            });

            return;
        }

        $this->app->singleton(TracerProviderInterface::class, function () {
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
        });

        $this->app->bindIf(MeterProviderInterface::class, function () {
            $exporter = new MetricExporter($this->getOtlpTransport('/v1/metrics'), Temporality::CUMULATIVE);

            return MeterProvider::builder()
                ->addReader(new ExportingReader($exporter))
                ->build();
        });

        $this->app->bindIf(LoggerProvider::class, function () {
            $exporter = new LogsExporter($this->getOtlpTransport('/v1/logs'));
            $processor = (new BatchLogRecordProcessor($exporter, Clock::getDefault()));

            register_shutdown_function(fn () => $processor->shutdown());

            return LoggerProvider::builder()
                ->addLogRecordProcessor($processor)
                ->build();
        });

        $this->app->singleton(OpenTelemetryConfig::class, function () {
            $appName = config('app.name', 'laravel');

            $meterProvider = $this->app->make(MeterProviderInterface::class);
            $meter = $meterProvider->getMeter($appName);

            $loggerProvider = $this->app->make(LoggerProvider::class);
            $logger = $loggerProvider->getLogger($appName);

            $eventLogger = new EventLogger($logger, Clock::getDefault());

            return new OpenTelemetryConfig($meterProvider, $meter, $loggerProvider, $eventLogger);
        });

        // Handler for sending `Log::...` calls to the OpenTelemetry collector
        $this->app->bindIf(Handler::class, function () {
            return new Handler(
                $this->app->make(OpenTelemetryConfig::class)->loggerProvider,
                'info',
                true,
            );
        });
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
        return (app(OtlpHttpTransportFactory::class))
            ->create($this->config->openTelemetry('dsn') . $path, $contentType, [], null, 1, 100, 1);
    }

    /**
     * Bootstrap any application services
     */
    public function boot(): void
    {
        if (!$this->openTelemetryIsInstalled()) {
            return;
        }

        // Setting a Logger on the LoggerHolder means that if the OpenTelemetry Collector
        // is not available, the logs will still be sent to stderr instead of throwing an exception
        LoggerHolder::set(new Logger('otel', [new StreamHandler('php://stderr')]));
    }

    /**
     * Check if OpenTelemetry is installed
     */
    private function openTelemetryIsInstalled(): bool
    {
        return class_exists(OtlpHttpTransportFactory::class);
    }
}
