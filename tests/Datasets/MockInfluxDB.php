<?php

declare(strict_types=1);

use InfluxDB\Database;

dataset('influx db mocks', [
    'Mocked InfluxDB for Flush Tests' => function (): Closure {
        return static function (): void {
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
                ->andReturn($mockDatabase);
        };
    },
]);
