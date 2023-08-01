<?php

namespace Stickee\Instrumentation\Laravel;

use Exception;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
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
use OpenTelemetry\SDK\Logs\LoggerProvider;
use OpenTelemetry\SDK\Logs\Processor\SimpleLogRecordProcessor;
use OpenTelemetry\SDK\Metrics\MeterProvider;
use OpenTelemetry\SDK\Metrics\MetricReader\ExportingReader;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOnSampler;
use OpenTelemetry\SDK\Trace\SamplerInterface;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\SpanProcessorInterface;
use OpenTelemetry\SemConv\ResourceAttributes;
use PlunkettScott\LaravelOpenTelemetry\OtelApplicationServiceProvider;
use Stickee\Instrumentation\Databases\DatabaseInterface;
use Stickee\Instrumentation\Databases\InfluxDb;
use Stickee\Instrumentation\Databases\Log as LogDatabase;
use Stickee\Instrumentation\Databases\NullDatabase;
use Stickee\Instrumentation\Laravel\Http\Middleware\InstrumentationResponseTimeMiddleware;
use Stickee\Instrumentation\Utils\OpenTelemetryConfig;

/**
 * Instrumentation service provider
 */
class ServiceProvider extends OtelApplicationServiceProvider
{
    /**
     * Return an implementation of SamplerInterface to use for sampling traces
     *
     * @return \OpenTelemetry\SDK\Trace\SamplerInterface
     */
    public function sampler(): SamplerInterface
    {
        return new AlwaysOnSampler();
    }

    /**
     * Return a ResourceInfo instance to merge with default resource attributes
     *
     * @return \OpenTelemetry\SDK\Resource\ResourceInfo
     */
    public function resourceInfo(): ResourceInfo
    {
        return ResourceInfo::create(Attributes::create([
            ResourceAttributes::SERVICE_NAME => config('app.name', 'Laravel'),
            ResourceAttributes::DEPLOYMENT_ENVIRONMENT => config('app.env', 'production'),
        ]));
    }

    /**
     * Return an array of additional processors to add to the tracer provider.
     *
     * @return SpanProcessorInterface[]
     */
    public function spanProcessors(): array
    {
        $exporter = new SpanExporter($this->getOtlpTransport('/v1/traces', 'application/x-protobuf'));

        return [
            // TODO use batches
            new SimpleSpanProcessor($exporter),
        ];
    }

    /**
     * Register the service provider
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/instrumentation.php', 'instrumentation'
        );

        $this->app->when(InfluxDb::class)
            ->needs('$dsn')
            ->give(fn () => $this->getDsn());

        $this->app->when(InfluxDb::class)
            ->needs('$verifySsl')
            ->give(fn () => config('instrumentation.verifySsl', true));

        $this->app->when(LogDatabase::class)
            ->needs('$filename')
            ->give(function() {
                $value = config('instrumentation.filename');

                if (empty($value)) {
                    throw new Exception('Config variable `instrumentation.filename` not set');
                }

                return $value;
            });

        $this->app->singleton('instrument', function(Application $app) {
            $class = config('instrumentation.enabled')
                ? config('instrumentation.database')
                : NullDatabase::class;

            if (empty($class)) {
                throw new Exception('Config variable `instrumentation.database` not set');
            }

            if (!class_exists($class, true)) {
                throw new Exception('Config variable `instrumentation.database` class not found: ' . $class);
            }

            if (!is_a($class, DatabaseInterface::class, true)) {
                throw new Exception('Config variable `instrumentation.database` does not implement \Stickee\Instrumentation\Databases\DatabaseInterface: ' . $class);
            }

            $database = $app->make($class);
            $database->setErrorHandler(function (Exception $e) {
                Log::error($e->getMessage());
            });

            return $database;
        });

        if (config('instrumentation.enabled')) {
            $this->registerOpenTelemetry();
        }

        parent::register();
    }

    /**
     * Register Open Telemetry classes
     */
    private function registerOpenTelemetry(): void
    {
        $this->app->bindIf(MeterProviderInterface::class, function () {
            $exporter = new MetricExporter($this->getOtlpTransport('/v1/metrics'));

            return MeterProvider::builder()
                ->addReader(new ExportingReader($exporter))
                ->build();
        });

        $this->app->bindIf(LoggerProvider::class, function () {
            $exporter = new LogsExporter($this->getOtlpTransport('/v1/logs'));

            return LoggerProvider::builder()
                ->addLogRecordProcessor(new SimpleLogRecordProcessor($exporter)) // TODO use batches
                ->build();
        });

        $this->app->singleton(OpenTelemetryConfig::class, function () {
            $appName = config('app.name', 'laravel');

            $meterProvider = $this->app->make(MeterProviderInterface::class);
            $meter = $meterProvider->getMeter($appName);

            $loggerProvider = $this->app->make(LoggerProvider::class);
            $logger = $loggerProvider->getLogger($appName);

            // TODO do we need this?
            // register_shutdown_function(static fn () => $loggerProvider->shutdown());

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
     * Get the DSN
     *
     * @return string
     */
    private function getDsn(): string
    {
        $dsn = config('instrumentation.dsn');

        if (empty($dsn)) {
            throw new Exception('Config variable `instrumentation.dsn` not set');
        }

        return $dsn;
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
            ->create($this->getDsn() . $path, $contentType, [], null, 1, 100, 1);
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

        // TODO make this optional / configurable
        $this->registerResponseTimeMiddleware();

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
