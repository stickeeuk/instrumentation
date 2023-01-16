<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use InfluxDB\Database;
use Stickee\Instrumentation\Databases\InfluxDb;

beforeEach(function (): void {
    Config::set('instrumentation.dsn', $this::EXAMPLE_DSN);

    $this->database = app(InfluxDb::class);
});

it('can record an event', function (): void {
    $this->database->event('Event', [], 1.0);

    expect($this->database->getEvents())->toHaveCount(1);
});

it('can record an increase in a counter', function (): void {
    $this->database->count('Event', [], 1.0);

    expect($this->database->getEvents())->toHaveCount(1);
});

it('can record the current value of a gauge', function (): void {
    $this->database->gauge('Event', [], 1.0);

    expect($this->database->getEvents())->toHaveCount(1);
});


it('can flush any queued writes and persist to database', function (): void {
    $mockDatabase = mock(Database::class)
        ->expects('writePoints')
        ->withAnyArgs()
        ->atLeast()->once()
        ->andReturnNull()
        ->getMock();

    mock('overload:\InfluxDB\Client')
        ->expects('fromDSN')
        ->withAnyArgs()
        ->once()
        ->andReturn($mockDatabase)
        ->getMock();

    // Add a value to the events array to prevent early returning:
    $this->database->gauge('Event', [], 1.0);
    $this->database->flush();

    // Now the events array is empty, calling flush again will not call the above mocked methods:
    $this->database->flush();
});

it('will call flush on deconstruction', function (): void {
    $mockDatabase = mock(Database::class)
        ->expects('writePoints')
        ->withAnyArgs()
        ->atLeast()->once()
        ->andReturnNull()
        ->getMock();

    mock('overload:\InfluxDB\Client')
        ->expects('fromDSN')
        ->withAnyArgs()
        ->once()
        ->andReturn($mockDatabase)
        ->getMock();

    $this->database->gauge('Event', [], 1.0);

    unset($this->database);
});

it('can get the underlying array of events', function (): void {
    expect(app(InfluxDb::class)->getEvents())->toBeArray();
});
