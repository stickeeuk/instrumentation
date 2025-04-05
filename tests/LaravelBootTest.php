<?php

declare(strict_types=1);

use Stickee\Instrumentation\Laravel\Facades\Instrument;

it('it only initializes OpenTelemetry once', function (): void {
    // This will run out of memory or stack overflow if OpenTelemetry is booted
    // every time around the loop.
    for ($i = 0; $i < 600; ++$i) {
        Instrument::span('test', fn() => 0);
        $this->refreshApplication();
    }

    expect(true)->toBe(true);
});
