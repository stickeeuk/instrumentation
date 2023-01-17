<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use Stickee\Instrumentation\Databases\LaravelLog;

it('can write to the laravel log', function (string $event): void {
    Log::shouldReceive($event)->once()->andReturnNull();

    $log = new LaravelLog($event);
    $log->event($event);
})->with('valid rfc 5424 events');
