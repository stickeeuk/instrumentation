<?php

namespace Stickee\Instrumentation\Laravel;

use Exception;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use OpenTelemetry\API\Logs\EventLogger;
use OpenTelemetry\Contrib\Logs\Monolog\Handler;
use OpenTelemetry\Contrib\Otlp\LogsExporter;
use OpenTelemetry\Contrib\Otlp\MetricExporter;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Common\Export\Stream\StreamTransportFactory;
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
use Stickee\Instrumentation\Utils\OpenTelemetryConfig;

/**
 * Instrumentation service provider
 */
class ServiceProvider extends OtelApplicationServiceProvider
{
    /**
     * Return an implementation of SamplerInterface to use for sampling traces.
     */
    public function sampler(): SamplerInterface
    {
        return new AlwaysOnSampler();
    }

    /**
     * Return a ResourceInfo instance to merge with default resource attributes.
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
        return [
            new SimpleSpanProcessor(
                new SpanExporter(
                    (new OtlpHttpTransportFactory())
                        ->create(config('instrumentation.dsn') . '/v1/traces', 'application/x-protobuf'),
                ),
            ),
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
            ->give(function () {
                $value = config('instrumentation.dsn');

                if (empty($value)) {
                    throw new Exception('Config variable `instrumentation.dsn` not set');
                }

                return $value;
            });

        $this->app->when(InfluxDb::class)
            ->needs('$verifySsl')
            ->give(function () {
                return config('instrumentation.verifySsl', true);
            });

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
            $class = config('instrumentation.database');

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

        $this->app->bind(OpenTelemetryConfig::class, static function () {
            $endpoint = config('instrumentation.dsn');

            if (empty($endpoint)) {
                throw new Exception('Config variable `instrumentation.dsn` not set');
            }

            $transport = (new StreamTransportFactory())->create('php://stdout', 'application/json');
            $exporter = new MetricExporter($transport);

            $meterProvider = MeterProvider::builder()
                ->addReader(new ExportingReader($exporter))
                ->build();

            $meter = $meterProvider->getMeter('pet-insurance');

            $transport = (new OtlpHttpTransportFactory())->create($endpoint . '/v1/logs', 'application/json');
            $exporter = new LogsExporter($transport);

            $loggerProvider = LoggerProvider::builder()
                ->addLogRecordProcessor(new SimpleLogRecordProcessor($exporter))
                ->build();

            $logger = $loggerProvider->getLogger('pet-insurance');

            register_shutdown_function(static fn () => $loggerProvider->shutdown());

            $eventLogger = new EventLogger($logger, 'pet-insurance');

            return new OpenTelemetryConfig($meterProvider, $meter, $loggerProvider, $eventLogger);
        });

        $this->app->bind(Handler::class, static function () {
            $endpoint = config('instrumentation.dsn');

            if (empty($endpoint)) {
                throw new Exception('Config variable `instrumentation.dsn` not set');
            }

            $transport = (new OtlpHttpTransportFactory())->create($endpoint . '/v1/logs', 'application/json');
            $exporter = new LogsExporter($transport);

            $loggerProvider = LoggerProvider::builder()
                ->addLogRecordProcessor(new SimpleLogRecordProcessor($exporter))
                ->build();

            return new Handler(
                $loggerProvider,
                'info',
                true,
            );
        });

        parent::register();
    }

    /**
     * Bootstrap any application services
     */
    public function boot()
    {
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

        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__ . '/../../config/instrumentation.php' => config_path('instrumentation.php'),
        ]);
    }
}
