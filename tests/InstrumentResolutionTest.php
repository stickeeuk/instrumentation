<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Stickee\Instrumentation\Exporters\Events\NullEvents;
use Stickee\Instrumentation\Exporters\Spans\NullSpans;
use Stickee\Instrumentation\Tests\Fixtures\BadEventsExporter;
use Stickee\Instrumentation\Tests\Fixtures\BrokenEventsExporter;
use Stickee\Instrumentation\Tests\Fixtures\GoodEventsExporter;

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
    Config::set('instrumentation.events_exporter', BadEventsExporter::class);

    app('instrument');
})->throws(Exception::class);

it('will set the default error handler to a laravel log', function (): void {
    if (version_compare(app()->version(), '9.0.0', '<')) {
        $this::markTestSkipped('Test incompatible with Laravel 8.');
    }

    Config::set('instrumentation.events_exporter', BrokenEventsExporter::class);

    /** @var \Stickee\Instrumentation\Tests\Fixtures\BrokenEventsExporter $instrument */
    $instrument = app('instrument');

    Log::expects('error')
        ->once()
        ->andReturnNull();

    $instrument->setErrorHandler(fn (Exception $exception) => Log::error($exception));

    $instrument->event('test_event');
});
