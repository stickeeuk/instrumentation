<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

it('records a histogram for RED metrics', function (): void {
    $request = Request::create('/test');
    $middleware = new \Stickee\Instrumentation\Laravel\Http\Middleware\InstrumentationResponseTimeMiddleware();

    $middleware->handle($request, function (): Response {
        return new Response('ok', 200);
    });

    $middleware->handle($request, function (): Response {
        return new Response('not ok', 500);
    });

    // TODO add proper assertion

    /**
     * RED metrics:
     *
     * `http_server_request_duration_seconds_count` - the number of requests
     * `http_server_request_duration_seconds_count{http_response_status_code="500"}` - the number of errors
     * `http_server_request_duration_seconds_sum` - duration
     *
     * `http_server_request_duration_seconds_bucket{le="0.1"}` - number of requests that took less than 0.1s
     *
     * These will all reset to 0 after PHP shuts down. We will measure them by their rate of change:
     *
     * `rate(http_server_request_duration_seconds_count[5m])` - the rate of change of the number of requests
     * `sum by (http_response_status_code) (rate(http_server_request_duration_seconds_count[5m]))` - the rate of change of the number of requests by status code
     */
});
