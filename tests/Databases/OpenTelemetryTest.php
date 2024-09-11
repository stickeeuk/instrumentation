<?php

declare(strict_types=1);

use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\SDK\Common\Export\TransportInterface;
use Stickee\Instrumentation\Exporters\Events\OpenTelemetry;

beforeEach(function (): void {
    if (!class_exists(OtlpHttpTransportFactory::class)) {
        return;
    }

    $this->mockTransport = $this->createMock(TransportInterface::class);
    $this->mockTransport->method('contentType')
        ->willReturn('application/json');

    app()->bind(OtlpHttpTransportFactory::class, function () {
        return new class ($this->mockTransport) {
            private TransportInterface $mockTransport;

            public function __construct(TransportInterface $mockTransport)
            {
                $this->mockTransport = $mockTransport;
            }

            public function create(): TransportInterface
            {
                return $this->mockTransport;
            }
        };
    });

    $this->exporter = app(OpenTelemetry::class);
})->skip(!class_exists(OtlpHttpTransportFactory::class), 'Skipped: OpenTelemetry composer packages not installed');

it('can record an event', function (): void {
    $eventName = 'STICKEE TEST EVENT';

    $this->mockTransport->expects($this->once())
        ->method('send')
        ->with($this->stringContains($eventName));

    $this->exporter->event($eventName, []);

    $this->exporter->flush();
});
