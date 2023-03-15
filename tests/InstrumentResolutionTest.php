<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Stickee\Instrumentation\Tests\Fixtures\BadDatabase;
use Stickee\Instrumentation\Tests\Fixtures\GoodDatabase;

it('will throw an exception if the instrumentation database is not set', function (): void {
    Config::set('instrumentation.database');

    app('instrument');
})->throws(Exception::class);

it('will throw an exception if the given database class does not exist', function (): void {
    Config::set('instrumentation.database', '\App\NonExisting\Class');

    app('instrument');
})->throws(Exception::class);

it('will throw an exception if the given database class is not a database interface implementation', function (): void {
    Config::set('instrumentation.database', BadDatabase::class);

    app('instrument');
})->throws(Exception::class);

it('will set the default error handler to a laravel log', function (): void {
    if (version_compare('7.4.0', PHP_VERSION, '>')) {
        $this::markTestSkipped('Test currently incompatible with PHP 7.3.');
    }

    Config::set('instrumentation.database', GoodDatabase::class);
    $exception = 'Test exception!';

    Log::expects('error')
        ->once()
        ->with($exception)
        ->andReturnNull();

    /** @var \Stickee\Instrumentation\Tests\Fixtures\GoodDatabase $shrike */
    $shrike = app('instrument');

    expect($shrike->getErrorHandler())->toBeCallable();

    $shrike->testErrorHandler($exception);
});
