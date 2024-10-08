<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use OpenTelemetry\API\Instrumentation\Configurator;
use OpenTelemetry\Contrib\Otlp\LogsExporter;
use OpenTelemetry\Contrib\Otlp\MetricExporter;
use OpenTelemetry\SDK\Common\Export\TransportInterface;
use OpenTelemetry\SDK\Common\Future\CompletedFuture;
use OpenTelemetry\SDK\Logs\EventLoggerProvider;
use OpenTelemetry\SDK\Logs\LoggerProvider;
use OpenTelemetry\SDK\Logs\Processor\SimpleLogRecordProcessor;
use OpenTelemetry\SDK\Metrics\Data\Temporality;
use OpenTelemetry\SDK\Metrics\MeterProvider;
use OpenTelemetry\SDK\Metrics\MetricReader\ExportingReader;
use Stickee\Instrumentation\DataScrubbers\CallbackDataScrubber;
use Stickee\Instrumentation\DataScrubbers\DataScrubberInterface;
use Stickee\Instrumentation\DataScrubbers\DefaultDataScrubber;
use Stickee\Instrumentation\Exporters\Events\OpenTelemetry as OpenTelemetryEvents;
use Stickee\Instrumentation\Exporters\Exporter;
use Stickee\Instrumentation\Exporters\Spans\OpenTelemetry as OpenTelmetrySpans;

beforeEach(function (): void {
    $this->mockTransport = $this->createMock(TransportInterface::class);
    $this->mockTransport->method('contentType')
        ->willReturnCallback(fn() => 'application/json');

    $metricExporter = new MetricExporter($this->mockTransport, Temporality::CUMULATIVE);
    $this->metricReader = new ExportingReader($metricExporter);

    $meterProvider = MeterProvider::builder()
        ->addReader($this->metricReader)
        ->build();

    $logsExporter = new LogsExporter($this->mockTransport);
    $this->logProcessor = new SimpleLogRecordProcessor($logsExporter);
    $loggerProvider = LoggerProvider::builder()
        ->addLogRecordProcessor($this->logProcessor)
        ->build();

    $configurator = Configurator::create()
        ->withMeterProvider($meterProvider)
        ->withLoggerProvider($loggerProvider)
        ->withEventLoggerProvider(new EventLoggerProvider($loggerProvider));

    $this->scope = $configurator->activate();

    $this->exporter = new Exporter(app(OpenTelemetryEvents::class), app(OpenTelmetrySpans::class), new DefaultDataScrubber());
});

afterEach(function (): void {
    $this->scope->detach();
});

it('can record an event', function (): void {
    $eventName = 'STICKEE TEST EVENT';

    $this->mockTransport->expects($this->once())
        ->method('send')
        ->with($this->stringContains($eventName));

    $this->exporter->event($eventName, []);

    $this->metricReader->shutdown();
    $this->logProcessor->shutdown();
});

it('can record a log', function (): void {
    $logMessage = 'STICKEE TEST LOG';

    $this->mockTransport->expects($this->once())
        ->method('send')
        ->with($this->stringContains($logMessage));

    Log::info($logMessage);

    $this->metricReader->shutdown();
    $this->logProcessor->shutdown();
});

it('can scrub sensitive data from events', function (): void {
    // Events don't have tags at the moment so don't check for [REDACTED_EMAIL]
    $this->mockTransport->expects($this->once())
        ->method('send')
        ->with($this->logicalNot($this->stringContains('test@example.com')))
        ->willReturnCallback(fn() => new CompletedFuture(null));

    $this->exporter->event('STICKEE TEST EVENT', ['email' => 'test@example.com'], 1);

    $this->metricReader->shutdown();
    $this->logProcessor->shutdown();
});

it('can scrub sensitive data from counters', function (): void {
    $this->mockTransport->expects($this->once())
        ->method('send')
        ->with($this->logicalAnd(
            $this->logicalNot($this->stringContains('test@example.com')),
            $this->stringContains(
                DefaultDataScrubber::DEFAULT_REDACTIONS[DefaultDataScrubber::EMAIL_REGEX]
            )
        ))
        ->willReturnCallback(fn() => new CompletedFuture(null));

    $this->exporter->counter('STICKEE TEST COUNTER', ['email' => 'test@example.com'], 1);

    $this->metricReader->shutdown();
    $this->logProcessor->shutdown();
});

it('can scrub sensitive data from gauges', function (): void {
    $this->mockTransport->expects($this->once())
        ->method('send')
        ->with($this->logicalAnd(
            $this->logicalNot($this->stringContains('test@example.com')),
            $this->stringContains(
                DefaultDataScrubber::DEFAULT_REDACTIONS[DefaultDataScrubber::EMAIL_REGEX]
            )
        ))
        ->willReturnCallback(fn() => new CompletedFuture(null));

    $this->exporter->gauge('STICKEE TEST GAUGE', ['email' => 'test@example.com'], 1);

    $this->metricReader->shutdown();
    $this->logProcessor->shutdown();
});

it('can scrub sensitive data from histograms', function (): void {
    $this->mockTransport->expects($this->once())
        ->method('send')
        ->with($this->logicalAnd(
            $this->logicalNot($this->stringContains('test@example.com')),
            $this->stringContains(
                DefaultDataScrubber::DEFAULT_REDACTIONS[DefaultDataScrubber::EMAIL_REGEX]
            )
        ))
        ->willReturnCallback(fn() => new CompletedFuture(null));

    $this->exporter->histogram('STICKEE TEST HISTOGRAM', '', '', [1], 1, ['email' => 'test@example.com']);

    $this->metricReader->shutdown();
    $this->logProcessor->shutdown();
});

it('can scrub sensitive data from logs', function (): void {
    $this->mockTransport->expects($this->once())
        ->method('send')
        ->with($this->logicalNot($this->stringContains('test@example.com')))
        ->willReturnCallback(fn() => new CompletedFuture(null));

    Log::error('Email: test@example.com', ['email' => 'test@example.com']);

    $this->metricReader->shutdown();
    $this->logProcessor->shutdown();
});

it('can use a scrubber callback', function (): void {
    $this->mockTransport->expects($this->once())
        ->method('send')
        ->with($this->logicalAnd(
            $this->logicalNot($this->stringContains('test@example.com')),
            $this->stringContains('SCRUBBED')
        ))
        ->willReturnCallback(fn() => new CompletedFuture(null));

    $this->app->bind(DataScrubberInterface::class, fn() => new CallbackDataScrubber(fn($key, $value) => 'SCRUBBED'));

    Log::error('Email: test@example.com', ['email' => 'test@example.com']);

    $this->metricReader->shutdown();
    $this->logProcessor->shutdown();
});
