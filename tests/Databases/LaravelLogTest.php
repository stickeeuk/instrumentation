<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use Stickee\Instrumentation\Databases\LaravelLog;

it('can write to the laravel log for an event', function (string $event, array $tags): void {
    Log::shouldReceive($event)->once()->andReturnNull();

    $log = new LaravelLog($event);
    $log->event($event, $tags);
})->with('valid rfc 5424 events', 'writable values');

it('can write to the laravel log for a count', function (string $event, array $tags): void {
    Log::shouldReceive($event)->once()->andReturnNull();

    $log = new LaravelLog($event);
    $log->count($event, $tags);
})->with('valid rfc 5424 events', 'writable values');

it('can write to the laravel log for a gauge', function (string $event, array $tags): void {
    Log::shouldReceive($event)->once()->andReturnNull();

    $log = new LaravelLog($event);
    $log->gauge($event, $tags, 1.0);
})->with('valid rfc 5424 events', 'writable values');


it('will crash if given an invalid event', function (): void {
    $this->expectException(Throwable::class);

    $log = new LaravelLog('1234');
    $log->event('Event');
});
