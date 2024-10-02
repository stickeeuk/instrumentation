<?php

declare(strict_types=1);

use OpenTelemetry\API\Instrumentation\Configurator;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Common\Export\TransportInterface;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOnSampler;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use Stickee\Instrumentation\DataScrubbers\DefaultDataScrubber;
use Stickee\Instrumentation\Exporters\Events\OpenTelemetry as OpenTelemetryEvents;
use Stickee\Instrumentation\Exporters\Exporter;
use Stickee\Instrumentation\Exporters\Spans\OpenTelemetry as OpenTelmetrySpans;
use Stickee\Instrumentation\Utils\DataScrubbingSpanProcessor;

beforeEach(function (): void {
    $this->mockTransport = $this->createMock(TransportInterface::class);
    $this->mockTransport->method('contentType')
        ->willReturn('application/json');

    $sampler = new AlwaysOnSampler();
    $exporter = new SpanExporter($this->mockTransport);
    $this->processor = new SimpleSpanProcessor($exporter);

    $tracerProvider = TracerProvider::builder()
        ->setSampler($sampler)
        ->addSpanProcessor(new DataScrubbingSpanProcessor(new DefaultDataScrubber()))
        ->addSpanProcessor($this->processor)
        ->build();

    $configurator = Configurator::create()
        ->withTracerProvider($tracerProvider);

    $this->scope = $configurator->activate();

    $this->exporter = new Exporter(app(OpenTelemetryEvents::class), app(OpenTelmetrySpans::class));
});

it('can scrub sensitive data', function (): void {
    $this->mockTransport->expects($this->once())
        ->method('send')
        ->with($this->logicalNot($this->stringContains('test@example.com')));

    $this->exporter->span('STICKEE TEST SPAN', function (): void {
        $this->exporter->event('STICKEE TEST EVENT');
    }, SpanKind::KIND_INTERNAL, ['email' => 'test@example.com']);

    $this->processor->shutdown();
    $this->scope->detach();
});
