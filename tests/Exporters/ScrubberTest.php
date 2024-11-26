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
use Stickee\Instrumentation\DataScrubbers\RegexDataScrubber;
use Stickee\Instrumentation\Exporters\Events\OpenTelemetry as OpenTelemetryEventsExporter;
use Stickee\Instrumentation\Exporters\Exporter;
use Stickee\Instrumentation\Exporters\Spans\OpenTelemetry as OpenTelmetrySpansExporter;

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

    $this->exporter = new Exporter(
        app(OpenTelemetryEventsExporter::class),
        app(OpenTelmetrySpansExporter::class),
        app(DataScrubberInterface::class)
    );
});

afterEach(function (): void {
    $this->scope->detach();
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
                RegexDataScrubber::DEFAULT_REGEX_REPLACEMENTS[RegexDataScrubber::EMAIL_REGEX]
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
                RegexDataScrubber::DEFAULT_REGEX_REPLACEMENTS[RegexDataScrubber::EMAIL_REGEX]
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
                RegexDataScrubber::DEFAULT_REGEX_REPLACEMENTS[RegexDataScrubber::EMAIL_REGEX]
            )
        ))
        ->willReturnCallback(fn() => new CompletedFuture(null));

    $this->exporter->histogram(
        name: 'STICKEE TEST HISTOGRAM',
        unit: '',
        description: '',
        buckets: [1],
        attributes: ['email' => 'test@example.com'],
        value: 1
    );

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

it('can scrub config data', function (): void {
    $this->mockTransport->expects($this->once())
        ->method('send')
        ->with($this->logicalNot($this->stringContains(config('app.key'))))
        ->willReturnCallback(fn() => new CompletedFuture(null));

    Log::error('App Key: ' . config('app.key'));

    $this->metricReader->shutdown();
    $this->logProcessor->shutdown();
});

it('can scrub arrays and objects', function (): void {
    $this->mockTransport->expects($this->once())
        ->method('send')
        ->with($this->logicalNot($this->stringContains(config('app.key'))))
        ->willReturnCallback(fn() => new CompletedFuture(null));

    $error = null;

    // Pre-hook errors don't throw exceptions normally, so capture them ourselves
    set_error_handler(function () use (&$error) {
        $error = func_get_args();
    });
    $f = fopen('php://memory', 'rb');
    $data = ['array' => ['test-array'], 'object' => (object) ['testObject' => 'test-object', 'file' => $f]];
    Log::error($data, $data);
    fclose($f);
    restore_error_handler();

    if ($error) {
        $this->fail('Error occurred: ' . $error[1]);
    }

    $this->metricReader->shutdown();
    $this->logProcessor->shutdown();
});

it('can limit data length', function (): void {
    $this->mockTransport->expects($this->once())
        ->method('send')
        ->with(
            $this->logicalAnd(
                $this->logicalNot($this->stringContains('XXXXXX')),
                $this->stringContains('XXX'),
                $this->logicalNot($this->stringContains('YYYYYY')),
                $this->stringContains('YYY')
            )
        )
        ->willReturnCallback(fn() => new CompletedFuture(null));

    config(['instrumentation.scrubbing.max_length' => 3]);
    Log::error('XXXXXX', ['YYYYYY']);
    config(['instrumentation.scrubbing.max_length' => 10240]);

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
