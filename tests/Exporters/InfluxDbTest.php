<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use InfluxDB2\Client;
use InfluxDB2\WriteApi;
use Stickee\Instrumentation\Exporters\Events\InfluxDb;

const INFLUX_EVENT = 'Event';
const INFLUX_TAGS = [];
const INFLUX_AMOUNT = 1.0;

describe('InfluxDb', function (): void {
    beforeEach(function (): void {
        if (! class_exists(Client::class)) {
            return;
        }

        $this->writeApiMock = $this->mock(WriteApi::class)
            ->shouldReceive('close')
            ->once()
            ->getMock();

        app()->bind(Client::class, function (): Client {
            return $this->mock(Client::class)
                ->shouldReceive('createWriteApi')
                ->once()
                ->andReturn($this->writeApiMock)
                ->getMock();
        });

        Config::set('instrumentation.dsn', 'https+influxdb://username:password@localhost:8086/databasename');

        $this->exporter = app(InfluxDb::class);
    })->skip(! class_exists(Client::class), 'Skipped: InfluxDB composer packages not installed');

    it('can record an event', function (): void {
        $this->writeApiMock->shouldReceive('write')
            ->once()
            ->withAnyArgs();

        $this->exporter->event(INFLUX_EVENT, INFLUX_TAGS);
        $this->exporter->flush();
    });

    it('can record an increase in a counter', function (): void {
        $this->writeApiMock->shouldReceive('write')
            ->once()
            ->withAnyArgs();

        $this->exporter->counter(INFLUX_EVENT, INFLUX_TAGS, INFLUX_AMOUNT);
        $this->exporter->flush();
    });

    it('can record the current value of a gauge', function (): void {
        $this->writeApiMock->shouldReceive('write')
            ->once()
            ->withAnyArgs();

        $this->exporter->gauge(INFLUX_EVENT, INFLUX_TAGS, INFLUX_AMOUNT);
        $this->exporter->flush();
    });

    it('it only sends events if there are events to send', function (): void {
        $this->writeApiMock->shouldReceive('write')
            ->once()
            ->withAnyArgs();

        // Add a value to the events array to prevent early returning:
        $this->exporter->gauge('Event', [], 1.0);
        $this->exporter->flush();

        // Now the events array is empty, calling flush again will not call the above mocked methods:
        $this->exporter->flush();
    });

    it('will call flush on destruction', function (): void {
        $this->writeApiMock->shouldReceive('write')
            ->once()
            ->withAnyArgs();

        $this->exporter->gauge('Event', [], 1.0);

        unset($this->exporter);
    });
});

describe('InfluxDb 2', function (): void {
    beforeEach(function (): void {
        Config::set('instrumentation.dsn', 'https+influxdb://username:password@localhost:8086/databasename');
    })->skip(! class_exists(Client::class), 'Skipped: InfluxDB composer packages not installed');

    it('will call handle error if an exception is encountered whilst flushing', function (): void {
        $exception = new Exception();

        $writeApiMock = $this->mock(WriteApi::class)
            ->shouldReceive('write')
            ->shouldReceive('close')
            ->between(1, 2) // TODO CHECK("Called once in flush, once in destructor")
            ->andThrow($exception)
            ->getMock();

        app()->bind(Client::class, function () use ($writeApiMock): Client {
            return $this->mock(Client::class)
                ->shouldReceive('createWriteApi')
                ->between(1, 2) // TODO CHECK("Called once in flush, once in destructor")
                ->andReturn($writeApiMock)
                ->getMock();
        });

        $mockEventsExporter = app(InfluxDb::class);
        $hasHandledError = false;

        $mockEventsExporter->setErrorHandler(function (Exception $e) use (&$hasHandledError, $exception): void {
            $hasHandledError = $exception === $e;
        });

        $mockEventsExporter->gauge('Event', [], 1.0);
        $mockEventsExporter->flush();

        $this->expect($hasHandledError)->toBeTrue();
    });
});
