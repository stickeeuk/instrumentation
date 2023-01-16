<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Stickee\Instrumentation\Databases\InfluxDb;

it('can be resolved by the service container by automatically fetching the dsn from config', function (): void {
    Config::set('instrumentation.dsn', $this::EXAMPLE_DSN);

    $influx = app(InfluxDb::class);

    expect($influx)->toBeInstanceOf(InfluxDb::class);
});

it('will throw an error when attempting to resolve dsn automatically if the config is not set', function (): void {
    Config::set('instrumentation.dsn');

    app(InfluxDb::class);
})->throws(Exception::class);

it('will automatically retrieve ssl verification settings from config when resolved by the service container', function (): void {
    Config::set('instrumentation.dsn', $this::EXAMPLE_DSN);
    Config::set('instrumentation.verifySsl', false);

    $influx = app(InfluxDb::class);

    expect($influx)->toBeInstanceOf(InfluxDb::class);
});
