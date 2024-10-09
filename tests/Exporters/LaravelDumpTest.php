<?php

declare(strict_types=1);

use phpmock\mockery\PHPMockery;
use Stickee\Instrumentation\Exporters\Events\LaravelDump;

beforeEach(function (): void {
    $this->exporter = new LaravelDump();
    $this->message = $this->faker()->sentence();

    PHPMockery::mock('\\Stickee\\Instrumentation\\Exporters\\Events\\', 'dump')
        ->withAnyArgs()
        ->once()
        ->andReturn(null);
});

it('will write to the symfony dump method on an event', function (array $attributes): void {
    $this->exporter->event($this->message, $attributes);
})->with('writable values');

it('will write to the symfony dump method on a counter', function (array $attributes): void {
    $this->exporter->counter($this->message, $attributes);
})->with('writable values');

it('will write to the symfony dump method on a gauge', function (array $attributes): void {
    $this->exporter->gauge($this->message, $attributes, 1.0);
})->with('writable values');
