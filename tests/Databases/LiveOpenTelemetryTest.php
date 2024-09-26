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

it ('records data for a while', function (): void {

    $transport = (app(\OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory::class))
        ->create('http://localhost:4318/v1/metrics', 'application/json', [], null, 1, 100, 1);
    $exporter = new \OpenTelemetry\Contrib\Otlp\MetricExporter($transport, \OpenTelemetry\SDK\Metrics\Data\Temporality::CUMULATIVE);

    $minutes = 15;
    $users = 100;

    $start = Carbon::now()->subMinutes($minutes);
    $startTimestamp = $start->getTimestampMs() * 1_000_000;

    $results = [
        '200' => 0,
        '400' => 0,
        '500' => 0,
    ];

    for ($second = 0; $second < $minutes * 60; $second++) {
        $metrics = [];

        for ($i = 0; $i < $users; $i++) {
            $routes = [
                '/' => [
                    'weight' => 100,
                    'time' => rand(500, 1000) / 1000,
                ],
                '/api/examples/1' => [
                    'weight' => 95,
                    'time' => rand(1000, 2000) / 1000,
                ],
                '/about' => [
                    'weight' => 50,
                    'time' => rand(750, 1000) / 1000,
                ],
                '/register' => [
                    'weight' => 10,
                    'time' => rand(1000, 2000) / 1000,
                ],
            ];

            $totalWeight = array_reduce($routes, function ($carry, $route) {
                return $carry + $route['weight'];
            }, 0);
            $selection = rand(1, $totalWeight);
            $count = 0;
            $chosen = [
                'route' => null,
                'time' => null,
            ];

            foreach ($routes as $route => $data) {
                $chosen['route'] = $route;
                $chosen['time'] = $data['time'];
                $count += $data['weight'];
                if ($count >= $selection) {
                    break;
                }
            }

            $chance = rand(0, 100);

            if ($chosen['route'] === '/') {
                $chance = 0;
            }

            if ($chance <= 90) {
                $response = new Response('ok', 200);
                $results['200']++;
            } elseif ($chance <= 98) {
                $response = new Response('kinda not ok', 400);
                $results['400']++;
            } else {
                $response = new Response('definitely not ok', 500);
                $results['500']++;
            }

            $request = Request::create($chosen['route']);
            $timestamp = $start->clone()->addSeconds($second)->getTimestampMs() * 1_000_000;

            $metrics[] = new \OpenTelemetry\SDK\Metrics\Data\Metric(
                new \OpenTelemetry\SDK\Common\Instrumentation\InstrumentationScope(
                    'test',
                    null,
                    null,
                    \OpenTelemetry\SDK\Common\Attribute\Attributes::create([]),
                ),
                \OpenTelemetry\SDK\Resource\ResourceInfoFactory::emptyResource(),
                \Stickee\Instrumentation\Utils\SemConv::HTTP_SERVER_REQUEST_DURATION_NAME,
                \Stickee\Instrumentation\Utils\SemConv::HTTP_SERVER_REQUEST_DURATION_UNIT,
                \Stickee\Instrumentation\Utils\SemConv::HTTP_SERVER_REQUEST_DURATION_DESCRIPTION,
                new \OpenTelemetry\SDK\Metrics\Data\Histogram([
                    new \OpenTelemetry\SDK\Metrics\Data\HistogramDataPoint(
                        count: $i + 1, // Each request increments the count by 1
                        sum: (float) $chosen['time'], // Sum is the request duration
                        min: 0,
                        max: 100,
                        bucketCounts: array_map(function ($bound) use ($chosen) {
                            return $chosen['time'] <= $bound ? 1 : 0;
                        }, \Stickee\Instrumentation\Utils\SemConv::HTTP_SERVER_REQUEST_DURATION_BUCKETS),
                        explicitBounds: \Stickee\Instrumentation\Utils\SemConv::HTTP_SERVER_REQUEST_DURATION_BUCKETS,
                        attributes: \OpenTelemetry\SDK\Common\Attribute\Attributes::create([
                            'http.response.status_code' => $response->getStatusCode(),
                            'http.request.method' => $request->method(),
                            'http.route' => $request->path(),
                        ]),
                        startTimestamp: $startTimestamp,
                        timestamp: $timestamp,
                        exemplars: []
                    ),
                ], \OpenTelemetry\SDK\Metrics\Data\Temporality::CUMULATIVE)
            );
        }
        $exporter->export($metrics);
        $metrics = [];
    }

    dump($results);

    // TODO look at Grafana
});
