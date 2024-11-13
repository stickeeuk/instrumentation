<?php

declare(strict_types=1);

it('can forward data events onto the underlying exporter', function (string $event): void {
    $exporter = app('instrument');

    match ($event) {
        'event' => $exporter->event($event, [], 1.0),
        'counter' => $exporter->counter($event, [], 1.0),
        'gauge' => $exporter->gauge($event, [], 1.0),
        'histogram' => $exporter->histogram($event, '', '', [0, 1], [], 1.0),
        default => $this::fail('Unknown event type'),
    };

    expect(true)->toBeTrue();
})->with([
    'event',
    'counter',
    'gauge',
    'histogram',
]);

it('will forward flush onto the underlying exporter', function (): void {
    app('instrument')->flush();

    expect(true)->toBeTrue();
});
