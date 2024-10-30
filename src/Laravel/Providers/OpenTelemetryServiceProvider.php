<?php

namespace Stickee\Instrumentation\Laravel\Providers;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use OpenTelemetry\API\Common\Time\Clock;
use OpenTelemetry\API\LoggerHolder;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SDK\Sdk;
use OpenTelemetry\SDK\Trace\ExporterFactory as TraceExporterFactory;
use OpenTelemetry\SDK\Trace\Sampler\TraceIdRatioBasedSampler;
use OpenTelemetry\SDK\Trace\Span;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;
use OpenTelemetry\SDK\Trace\SpanProcessor\MultiSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SDK\Trace\TracerProviderInterface;
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
    #[\Override]
    public function register(): void
    {
        $this->config = $this->app->make(Config::class);

        $this->app->singleton(CachedInstruments::class, fn(): \Stickee\Instrumentation\Utils\CachedInstruments => new CachedInstruments('uk.co.stickee.instrumentation'));
    }

    /**
     * Bootstrap any application services
     */
    public function boot(): void
    {
        if (! $this->config->enabled()) {
            return;
        }

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

        Sdk::builder()
            ->setTracerProvider($this->getTracerProvider())
            ->setPropagator(TraceContextPropagator::getInstance())
            ->setAutoShutdown(true)
            ->buildAndRegisterGlobal();
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

        $exporter = (new TraceExporterFactory())->create();
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

        return TracerProvider::builder()
            ->setSampler($sampler)
            ->setResource(ResourceInfoFactory::defaultResource())
            ->addSpanProcessor(app(DataScrubbingSpanProcessor::class))
            ->addSpanProcessor($processor)
            ->build();
    }
}
