<?php

declare(strict_types=1);

use phpmock\phpunit\PHPMock;
use Stickee\Instrumentation\Databases\LaravelDump;

uses(PHPMock::class);

beforeEach(function (): void {
    $this->database = new LaravelDump();
    $this->message = fake()->sentence();

    $this
        ->getFunctionMock('\\Stickee\\Instrumentation\\Databases\\', 'dump')
        ->expects($this::once())
        ->withAnyParameters()
        ->willReturn(null);
});

it('will write to the symfony dump method on an event', function (array $tags): void {
    $this->database->event($this->message, $tags);
})->with('writable values');
