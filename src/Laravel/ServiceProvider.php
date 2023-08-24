<?php

namespace Stickee\Instrumentation\Laravel;

use Exception;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use OpenTelemetry\API\LoggerHolder;
use OpenTelemetry\API\Logs\EventLogger;
use OpenTelemetry\Contrib\Logs\Monolog\Handler;
use OpenTelemetry\Contrib\Otlp\LogsExporter;
use OpenTelemetry\Contrib\Otlp\MetricExporter;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Common\Export\Http\PsrTransport;
use OpenTelemetry\SDK\Common\Time\ClockFactory;
use OpenTelemetry\SDK\Logs\LoggerProvider;
use OpenTelemetry\SDK\Logs\Processor\BatchLogRecordProcessor;
use OpenTelemetry\SDK\Metrics\MeterProvider;
use OpenTelemetry\SDK\Metrics\MetricReader\ExportingReader;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOnSampler;
use OpenTelemetry\SDK\Trace\Sampler\TraceIdRatioBasedSampler;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SDK\Trace\TracerProviderInterface;
use OpenTelemetry\SemConv\ResourceAttributes;
use Stickee\Instrumentation\Exporters\Events\InfluxDb;
use Stickee\Instrumentation\Exporters\Events\LogFile;
use Stickee\Instrumentation\Exporters\Events\OpenTelemetry;
use Stickee\Instrumentation\Exporters\Exporter;
use Stickee\Instrumentation\Laravel\Config;
use Stickee\Instrumentation\Laravel\Http\Middleware\InstrumentationResponseTimeMiddleware;
use Stickee\Instrumentation\Utils\OpenTelemetryConfig;

/**
 * Instrumentation service provider
 */
class ServiceProvider extends LaravelServiceProvider
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

        $this->mergeConfigFrom(
            __DIR__ . '/../../config/instrumentation.php', 'instrumentation'
        );

        $this->app->when(LogFile::class)
            ->needs('$filename')
            ->give(fn () => $this->config->logFile('filename'));

        $this->app->bind(Exporter::class, function(Application $app) {
            $eventsExporter = $app->make($this->config->eventsExporterClass());
            $spansExporter = $app->make($this->config->spansExporterClass());

            return new Exporter($eventsExporter, $spansExporter);
        });

        $this->app->singleton('instrument', function(Application $app) {
            $database = $app->make(Exporter::class);
            $database->setErrorHandler(function (Exception $e) {
                Log::error($e->getMessage());
            });

            return $database;
        });

        $this->registerInfluxDb();
        $this->registerOpenTelemetry();
    }

    /**
     * Register InfluxDb
     */
    private function registerInfluxDb(): void
    {
        if (!class_exists(InfluxDb::class)) {
            $this->app->bind(InfluxDb::class, function () {
                throw new Exception('InfluxDB client library not installed, please run: composer require influxdata/influxdb-client-php');
            });

            return;
        }

        $this->app->when(InfluxDb::class)
            ->needs('$url')
            ->give(fn () => $this->config->influxDb('url'));

        $this->app->when(InfluxDb::class)
            ->needs('$token')
            ->give(fn () => $this->config->influxDb('token'));

        $this->app->when(InfluxDb::class)
            ->needs('$bucket')
            ->give(fn () => $this->config->influxDb('bucket'));

        $this->app->when(InfluxDb::class)
            ->needs('$org')
            ->give(fn () => $this->config->influxDb('org'));

        $this->app->when(InfluxDb::class)
            ->needs('$verifySsl')
            ->give(fn () => $this->config->influxDb('verify_ssl'));
    }

    /**
     * Register Open Telemetry classes
     */
    private function registerOpenTelemetry(): void
    {
        if (!class_exists(AlwaysOnSampler::class)) {
            $this->app->bind(TracerProviderInterface::class, function () {
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
                ResourceAttributes::DEPLOYMENT_ENVIRONMENT => config('app.env', 'production'),
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
            $exporter = new MetricExporter($this->getOtlpTransport('/v1/metrics'));

            return MeterProvider::builder()
                ->addReader(new ExportingReader($exporter))
                ->build();
        });

        $this->app->bindIf(LoggerProvider::class, function () {
            $exporter = new LogsExporter($this->getOtlpTransport('/v1/logs'));
            $processor = (new BatchLogRecordProcessor($exporter, ClockFactory::getDefault()));

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

            $eventLogger = new EventLogger($logger, $appName);

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
     * @return \OpenTelemetry\SDK\Common\Export\Http\PsrTransport
     */
    private function getOtlpTransport(string $path, $contentType = 'application/json'): PsrTransport
    {
        return (new OtlpHttpTransportFactory())
            ->create($this->config->openTelemetry('dsn') . $path, $contentType, [], null, 1, 100, 1);
    }

    /**
     * Bootstrap any application services
     */
    public function boot(): void
    {
        // Setting a Logger on the LoggerHolder means that if the OpenTelemetry Collector
        // is not available, the logs will still be sent to stderr instead of throwing an exception
        LoggerHolder::set(new Logger('otel', [new StreamHandler('php://stderr')]));

        // Flush events when a command finishes
        Event::listen('Illuminate\Console\Events\CommandFinished', function () {
            app('instrument')->flush();
        });

        // Flush events when a queue job completes
        Queue::after(function () {
            app('instrument')->flush();
        });

        // Flush events when a queue job fails
        Queue::failing(function () {
            app('instrument')->flush();
        });

        if ($this->app->runningUnitTests()) {
            return;
        }

        if ($this->config->responseTimeMiddlewareEnabled()) {
            $this->registerResponseTimeMiddleware();
        }

        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__ . '/../../config/instrumentation.php' => config_path('instrumentation.php'),
        ]);
    }

    /**
     * Register the response time middleware
     */
    private function registerResponseTimeMiddleware(): void
    {
        // We attach to the HttpKernel, so we need it to be available.
        if (!$this->app->bound(Kernel::class)) {
            return;
        }

        /** @var \use Illuminate\Contracts\Http\Kernel $httpKernel */
        $httpKernel = $this->app->make(Kernel::class);

        $httpKernel->prependMiddleware(InstrumentationResponseTimeMiddleware::class);
    }
}
