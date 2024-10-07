<?php

namespace Stickee\Instrumentation\Laravel\Providers;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use OpenTelemetry\API\Common\Time\Clock;
use OpenTelemetry\API\Instrumentation\Configurator;
use OpenTelemetry\API\LoggerHolder;
use OpenTelemetry\Contrib\Otlp\LogsExporter;
use OpenTelemetry\Contrib\Otlp\MetricExporter;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Common\Export\TransportInterface;
use OpenTelemetry\SDK\Logs\EventLoggerProvider;
use OpenTelemetry\SDK\Logs\LoggerProvider;
use OpenTelemetry\SDK\Logs\LoggerProviderInterface;
use OpenTelemetry\SDK\Logs\Processor\BatchLogRecordProcessor;
use OpenTelemetry\SDK\Metrics\Data\Temporality;
use OpenTelemetry\SDK\Metrics\MeterProvider;
use OpenTelemetry\SDK\Metrics\MeterProviderInterface;
use OpenTelemetry\SDK\Metrics\MetricReader\ExportingReader;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SDK\Trace\Sampler\TraceIdRatioBasedSampler;
use OpenTelemetry\SDK\Trace\Span;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;
use OpenTelemetry\SDK\Trace\SpanProcessor\MultiSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SDK\Trace\TracerProviderInterface;
use OpenTelemetry\SemConv\ResourceAttributes;
use Stickee\Instrumentation\Exporters\Events\OpenTelemetry;
use Stickee\Instrumentation\Laravel\Config;
use Stickee\Instrumentation\Utils\CachedInstruments;
use Stickee\Instrumentation\Utils\DataScrubbingSpanProcessor;
use Stickee\Instrumentation\Utils\RecordSampler;
use Stickee\Instrumentation\Utils\SlowSpanProcessor;

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
     * Register the service provider
     */
    public function register(): void
    {
        $this->config = $this->app->make(Config::class);

        $this->app->singleton(CachedInstruments::class, fn() => new CachedInstruments('uk.co.stickee.instrumentation'));
    }

    /**
     * Bootstrap any application services
     */
    public function boot(): void
    {
        static $firstBoot = true;

        if (! $this->config->enabled()) {
            return;
        }

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

        // Send logs as span events as well as log events
        $this->app['events']->listen(MessageLogged::class, function (MessageLogged $log): void {
            Span::getCurrent()->addEvent(Str::limit($log->message, 127), [
                'context' => json_encode(array_filter($log->context)),
                'level' => $log->level,
            ]);
        });

        $loggerProvider = $this->getLoggerProvider();

        // In tests the application is booted multiple times but we don't need to create a new Configurator scope
        if (! $firstBoot) {
            return;
        }

        $firstBoot = false;

        $configurator = Configurator::create()
            ->withTracerProvider($this->getTracerProvider())
            ->withMeterProvider($this->getMeterProvider())
            ->withLoggerProvider($loggerProvider)
            ->withEventLoggerProvider(new EventLoggerProvider($loggerProvider));

        $scope = $configurator->activate();

        register_shutdown_function(fn() => $scope->detach());
    }

    /**
     * Create a tracer provider
     */
    private function getTracerProvider(): TracerProviderInterface
    {
        $traceLongRequests = $this->config->longRequestTraceThreshold() && ($this->config->traceSampleRate() < 1);
        $sampler = $traceLongRequests
            ? new RecordSampler(new TraceIdRatioBasedSampler($this->config->traceSampleRate()))
            : new TraceIdRatioBasedSampler($this->config->traceSampleRate());

        $resourceInfo = ResourceInfo::create(Attributes::create([
            ResourceAttributes::SERVICE_NAME => config('app.name', 'laravel'),
            ResourceAttributes::DEPLOYMENT_ENVIRONMENT_NAME => config('app.env', 'production'),
        ]));

        $exporter = new SpanExporter($this->getOtlpTransport('/v1/traces', 'application/x-protobuf'));
        $batchProcessor = BatchSpanProcessor::builder($exporter)->build();
        $processor = $traceLongRequests
            ? new MultiSpanProcessor(
                $batchProcessor,
                new SlowSpanProcessor(
                    $exporter,
                    Clock::getDefault(),
                    $this->config->longRequestTraceThreshold()
                )
            )
            : $batchProcessor;

        register_shutdown_function(fn() => $processor->shutdown());

        return TracerProvider::builder()
            ->setSampler($sampler)
            ->setResource($resourceInfo->merge(ResourceInfoFactory::defaultResource()))
            ->addSpanProcessor(app(DataScrubbingSpanProcessor::class))
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

        register_shutdown_function(fn() => $reader->shutdown());

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

        register_shutdown_function(fn() => $processor->shutdown());

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
            ->create(
                endpoint: $this->config->openTelemetry('dsn') . $path,
                contentType: $contentType,
                headers: [],
                compression: null,
                timeout: 1,
                retryDelay: 100,
                maxRetries: 1
            );
    }
}
