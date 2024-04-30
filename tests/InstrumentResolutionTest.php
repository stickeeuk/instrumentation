<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Stickee\Instrumentation\Tests\Fixtures\BadDatabase;
use Stickee\Instrumentation\Tests\Fixtures\GoodDatabase;
use Stickee\Instrumentation\Exporters\Events\NullEvents;
use Stickee\Instrumentation\Exporters\Spans\NullSpans;

it('will throw an exception if the events exporter is not set', function (): void {
    Config::set('instrumentation.events_exporter', NullEvents::class);
    Config::set('instrumentation.spans_exporter');

    app('instrument');
})->throws(Exception::class);

it('will throw an exception if the spans exporter is not set', function (): void {
    Config::set('instrumentation.events_exporter');
    Config::set('instrumentation.spans_exporter', NullSpans::class);

    app('instrument');
})->throws(Exception::class);

it('will throw an exception if the given database class does not exist', function (): void {
    Config::set('instrumentation.events_exporter', '\App\NonExisting\Class');

    app('instrument');
})->throws(Exception::class);

it('will throw an exception if the given database class is not a database interface implementation', function (): void {
    Config::set('instrumentation.events_exporter', BadDatabase::class);

    app('instrument');
})->throws(Exception::class);

it('will set the default error handler to a laravel log', function (): void {
    if ((int) substr(app()->version(), 0, 1) < 9) {
        $this::markTestSkipped('Test incompatible with Laravel 8.');
    }

    Config::set('instrumentation.database', GoodDatabase::class);
    $exception = 'Test exception!';

    Log::expects('error')
        ->once()
        ->with($exception)
        ->andReturnNull();

    /** @var \Stickee\Instrumentation\Tests\Fixtures\GoodDatabase $shrike */
    $shrike = app('instrument');

    // expect($shrike->getErrorHandler())->toBeCallable();

    // $shrike->testErrorHandler($exception);
});
