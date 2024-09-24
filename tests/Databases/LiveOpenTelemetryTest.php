<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use OpenTelemetry\API\Trace\SpanKind;
use Stickee\Instrumentation\Exporters\Events\OpenTelemetry as OpenTelemetryEventsExporter;
use Stickee\Instrumentation\Exporters\Spans\OpenTelemetry as OpenTelemetrySpansExporter;
use Stickee\Instrumentation\Laravel\Facades\Instrument;

beforeEach(function (): void {
    config(['instrumentation.events_exporter' => OpenTelemetryEventsExporter::class]);
    config(['instrumentation.spans_exporter' => OpenTelemetrySpansExporter::class]);
});

it('can record an event', function (): void {
    Instrument::span('STICKEE TEST SPAN', function (): void {
        Instrument::event('STICKEE TEST EVENT');
    });

    // TODO add proper assertion
});

it('auto instruments controller methods', function (): void {
    Route::get('/instrumentation-test/{test}', function (string $test): void {
        Instrument::span('STICKEE TEST SPAN', function () use ($test): void {
            Instrument::event('STICKEE TEST EVENT: ' . $test);
        });
    });

    $response = $this->get('/instrumentation-test/test');

    $response->assertOk();
    // TODO add proper assertion
});

it('auto instruments logs', function (): void {
    Instrument::span('STICKEE TEST LOG SPAN', function (): void {
        Log::info('STICKEE TEST LOG');
        Instrument::event('STICKEE TEST EVENT');
    }, SpanKind::KIND_INTERNAL, ['test' => 123]);

    // TODO add proper assertion
});

it('instruments requests', function (): void {
    Route::get('/instrumentation-test', function (): void {});

    $response = $this->get('/instrumentation-test');

    $response->assertOk();
    // TODO add proper assertion
});
