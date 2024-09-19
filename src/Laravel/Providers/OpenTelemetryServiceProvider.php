<?php

namespace Stickee\Instrumentation\Laravel\Providers;

use Illuminate\Support\ServiceProvider;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use OpenTelemetry\API\Common\Time\Clock;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Instrumentation\Configurator;
use OpenTelemetry\API\LoggerHolder;
use OpenTelemetry\Context\ScopeInterface;
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
     */
    private Config $config;

    /**
     * The current scope
     */
    private static ?ScopeInterface $currentScope = null;

    /**
     * Register the service provider
     */
    public function register(): void
    {
        $this->config = $this->app->make(Config::class);

        $this->app->bind(TracerProviderInterface::class, fn () => Globals::tracerProvider());
        $this->app->bind(MeterProviderInterface::class, fn () => Globals::meterProvider());
        $this->app->bind(LoggerProviderInterface::class, fn () => Globals::loggerProvider());
        $this->app->bind(EventLoggerProviderInterface::class, fn () => Globals::eventLoggerProvider());
    }

    /**
     * Bootstrap any application services
     */
    public function boot(): void
    {
        // DB::connection('mysql')->beforeExecuting(function (string &$query) {
        //     $uuid = Str::uuid()->toString();
        //     $query = '/* ' . $uuid . ' */ ' . $query;
        // });

        // $this->app['events']->listen(QueryExecuted::class, function (QueryExecuted $query): void {
        //     if (preg_match('/\/\* ([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[4][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}) \*\//', $query->sql, $matches)) {
        //         dump($matches[1]);
        //     }
        // });

        // Setting a Logger on the LoggerHolder means that if the OpenTelemetry Collector
        // is not available, the logs will still be sent to stderr instead of throwing an exception
        LoggerHolder::set(new Logger('otel', [new StreamHandler('php://stderr')]));

        $loggerProvider = $this->getLoggerProvider();

        $configurator = Configurator::create()
            ->withTracerProvider($this->getTracerProvider())
            ->withMeterProvider($this->getMeterProvider())
            ->withLoggerProvider($loggerProvider)
            ->withEventLoggerProvider(new EventLoggerProvider($loggerProvider));

        // In tests the application is booted multiple times, so we need to detach the current scope
        // and only detach the current one on shutdown
        $firstBoot = !self::$currentScope;

        if (!$firstBoot) {
            self::$currentScope->detach();
        }

        self::$currentScope = $configurator->activate();

        if ($firstBoot) {
            register_shutdown_function(fn () => self::$currentScope->detach());
        }
    }

    /**
     * Create a tracer provider
     */
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

    /**
     * Create a meter provider
     */
    private function getMeterProvider(): MeterProviderInterface
    {
        $exporter = new MetricExporter($this->getOtlpTransport('/v1/metrics'), Temporality::CUMULATIVE);
        $reader = new ExportingReader($exporter);

        register_shutdown_function(fn () => $reader->shutdown());

        return MeterProvider::builder()
            ->addReader($reader)
            ->build();
    }

    /**
     * Create a logger provider
     */
    private function getLoggerProvider(): LoggerProviderInterface
    {
        $exporter = new LogsExporter($this->getOtlpTransport('/v1/logs'));
        $processor = new BatchLogRecordProcessor($exporter, Clock::getDefault());

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
        return (app(OtlpHttpTransportFactory::class))
            ->create($this->config->openTelemetry('dsn') . $path, $contentType, [], null, 1, 100, 1);
    }
}
