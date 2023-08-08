<?php

declare(strict_types=1);

use Stickee\Instrumentation\Exporters\Events\Log;
use Stickee\Instrumentation\Exporters\Interfaces\EventsExporterInterface;
use Stickee\Instrumentation\Instrument;

test('set database will change the internal database implementation', function (): void {
    Instrument::setDatabase(new Log('test.log'));

    // Check whether the internal database has actually changed using black magic:
    $bypass = static function (): EventsExporterInterface {
        /** @noinspection PhpExpressionAlwaysNullInspection */
        return static::$database;
    };

    $bypass = $bypass->bindTo(null, Instrument::class);
    $database = $bypass();

    expect($database)
        ->toBeInstanceOf(EventsExporterInterface::class)
        ->toBeInstanceOf(Log::class);
});
