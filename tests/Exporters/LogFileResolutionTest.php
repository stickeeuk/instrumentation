<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Stickee\Instrumentation\Exporters\Events\LogFile;

it('will resolve the filename from the service container', function (): void {
    Config::set('instrumentation.log_file.filename', base_path('test.log'));

    $log = app(LogFile::class);

    expect($log)->toBeInstanceOf(LogFile::class);
});

it('will throw an error when attempting to resolve log filename if the config is not set', function (): void {
    Config::set('instrumentation.log_file.filename', '');

    app(LogFile::class);
})->throws(Exception::class);
