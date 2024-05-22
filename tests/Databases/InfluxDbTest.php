<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use InfluxDB\Database;
use Stickee\Instrumentation\Exporters\Events\InfluxDb;

const INFLUX_EVENT = 'Event';
const INFLUX_TAGS = [];
const INFLUX_AMOUNT = 1.0;

$skipAll = !class_exists(Database::class);

beforeEach(function () use ($skipAll): void {
    if ($skipAll) {
        return;
    }

    Config::set('instrumentation.dsn', $this::EXAMPLE_DSN);

    $this->database = app(InfluxDb::class);
});

it('can record an event', function (): void {
    $this->database->event(INFLUX_EVENT, INFLUX_TAGS);

    expect($this->database->getEvents())->toHaveCount(1);
})->skip('Test is incomplete');

it('can record an increase in a counter', function (): void {
    $this->database->count(INFLUX_EVENT, INFLUX_TAGS, INFLUX_AMOUNT);

    expect($this->database->getEvents())->toHaveCount(1);
})->skip('Test is incomplete');

it('can record the current value of a gauge', function (Closure $setupMocks): void {
    $setupMocks();

    $this->database->gauge(INFLUX_EVENT, INFLUX_TAGS, INFLUX_AMOUNT);

    expect($this->database->getEvents())->toHaveCount(1);
})->with('influx db mocks')->skip('Test is incomplete');


it('can flush any queued writes and persist to database', function (Closure $setupMocks): void {
    $setupMocks();

    // Add a value to the events array to prevent early returning:
    $this->database->gauge('Event', [], 1.0);
    $this->database->flush();

    // Now the events array is empty, calling flush again will not call the above mocked methods:
    $this->database->flush();
})->with('influx db mocks')->skip('Test is incomplete');

it('will call flush on deconstruction', function (Closure $setupMocks): void {
    $setupMocks();

    $this->database->gauge('Event', [], 1.0);

    unset($this->database);
})->with('influx db mocks')->skip('Test is incomplete');

it('will return an existing database if it already has one', function (Closure $setupMocks): void {
    //
})->with('influx db mocks')->skip('Test is incomplete');

it('will call handle error if an exception is encountered whilst flushing', function (): void {
    $mockInflux = Mockery::mock(InfluxDb::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods()
        ->expects('handleError')
        ->once()
        ->withAnyArgs()
        ->andReturnNull()
        ->getMock();

    $mockDatabase = Mockery::mock(Database::class)
        ->expects('writePoints')
        ->withAnyArgs()
        ->atLeast()->once()
        ->andThrow(Exception::class)
        ->getMock();

    $this->app->instance(\InfluxDB\Client::class, Mockery::mock('overload:\InfluxDB\Client')
        ->expects('fromDSN')
        ->withAnyArgs()
        ->once()
        ->andReturn($mockDatabase)
        ->getMock(),
    );

    $mockInflux->gauge('Event', [], 1.0);
    $mockInflux->flush();
})->skip('Test is incomplete');


it('can get the underlying array of events', function (): void {
    expect($this->database->getEvents())->toBeArray();
})->skip('Test is incomplete');
