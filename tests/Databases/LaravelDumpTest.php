<?php

declare(strict_types=1);

use phpmock\phpunit\PHPMock;
use Stickee\Instrumentation\Exporters\Events\LaravelDump;

uses(PHPMock::class);

beforeEach(function (): void {
    $this->database = new LaravelDump();
    $this->message = $this->faker()->sentence();

    $this->getFunctionMock('\\Stickee\\Instrumentation\\Exporters\\Events\\', 'dump')
        ->expects($this::once())
        ->withAnyParameters()
        ->willReturn(null);
});

it('will write to the symfony dump method on an event', function (array $tags): void {
    $this->database->event($this->message, $tags);
})->with('writable values');

it('will write to the symfony dump method on a counter', function (array $tags): void {
    $this->database->counter($this->message, $tags);
})->with('writable values');

it('will write to the symfony dump method on a gauge', function (array $tags): void {
    $this->database->gauge($this->message, $tags, 1.0);
})->with('writable values');
