<?php

declare(strict_types=1);

use Stickee\Instrumentation\Databases\DatabaseInterface;
use Stickee\Instrumentation\Databases\Log;
use Stickee\Instrumentation\Instrument;

test('set database will change the internal database implementation', function (): void {
    Instrument::setDatabase(new Log('test.log'));

    // Check whether the internal database has actually changed using black magic:
    $bypass = static function (): DatabaseInterface {
        /** @noinspection PhpExpressionAlwaysNullInspection */
        return static::$database;
    };

    $bypass = $bypass->bindTo(null, Instrument::class);
    $database = $bypass();

    expect($database)
        ->toBeInstanceOf(DatabaseInterface::class)
        ->toBeInstanceOf(Log::class);
});
