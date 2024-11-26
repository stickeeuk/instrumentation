<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use OpenTelemetry\API\Instrumentation\Configurator;
use OpenTelemetry\Contrib\Otlp\LogsExporter;
use OpenTelemetry\Contrib\Otlp\MetricExporter;
use OpenTelemetry\SDK\Common\Export\TransportInterface;
use OpenTelemetry\SDK\Logs\EventLoggerProvider;
use OpenTelemetry\SDK\Logs\LoggerProvider;
use OpenTelemetry\SDK\Logs\Processor\SimpleLogRecordProcessor;
use OpenTelemetry\SDK\Metrics\Data\Temporality;
use OpenTelemetry\SDK\Metrics\MeterProvider;
use OpenTelemetry\SDK\Metrics\MetricReader\ExportingReader;
use Stickee\Instrumentation\DataScrubbers\DataScrubberInterface;
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
