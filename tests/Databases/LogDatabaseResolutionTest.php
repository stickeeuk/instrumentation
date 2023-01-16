<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Stickee\Instrumentation\Databases\Log;

it('will resolve the filename from the service container', function (): void {
    Config::set('instrumentation.filename', base_path('test.log'));

    $log = app(Log::class);

    expect($log)->toBeInstanceOf(Log::class);
});

it('will throw an error when attempting to resolve log filename if the config is not set', function (): void {
    Config::set('instrumentation.filename');

    app(Log::class);
})->throws(Exception::class);
