<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use OpenTelemetry\Contrib\Otlp\LogsExporter;
use OpenTelemetry\SDK\Common\Export\TransportInterface;
use OpenTelemetry\SDK\Logs\EventLoggerProvider;
use OpenTelemetry\SDK\Logs\LoggerProvider;
use OpenTelemetry\SDK\Logs\Processor\SimpleLogRecordProcessor;
use Stickee\Instrumentation\Exporters\Events\OpenTelemetry;

beforeEach(function (): void {
    $this->mockTransport = $this->createMock(TransportInterface::class);
    $this->mockTransport->method('contentType')
        ->willReturn('application/json');

    $exporter = new LogsExporter($this->mockTransport);
    $this->processor = new SimpleLogRecordProcessor($exporter);

    $loggerProvider = LoggerProvider::builder()
        ->addLogRecordProcessor($this->processor)
        ->build();

    $this->exporter = app(OpenTelemetry::class, ['eventLoggerProvider' => new EventLoggerProvider($loggerProvider)]);
});

afterEach(function (): void {
    $this->processor->shutdown();
});

it('can record an event', function (): void {
    $eventName = 'STICKEE TEST EVENT';

    $this->mockTransport->expects($this->once())
        ->method('send')
        ->with($this->stringContains($eventName));

    $this->exporter->event($eventName, []);

    $this->exporter->flush();
});

it('can record a log', function (): void {
    $eventName = 'STICKEE TEST LOG';

    $this->mockTransport->expects($this->once())
        ->method('send')
        ->with($this->stringContains($eventName));

    // $this->exporter->event($eventName, []);
    Log::info($eventName);

    $this->exporter->flush();
});
