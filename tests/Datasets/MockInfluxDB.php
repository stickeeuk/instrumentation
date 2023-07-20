<?php

declare(strict_types=1);

use InfluxDB\Database;

dataset('influx db mocks', [
    'Mocked InfluxDB for Flush Tests' => function (): Closure {
        return function (): void {
            $mockDatabase = Mockery::mock(Database::class)
                ->expects('writePoints')
                ->withAnyArgs()
                ->atLeast()->once()
                ->andReturnNull()
                ->getMock();

            $this->app->instance(InfluxDB\Client::class, Mockery::mock('overload:\InfluxDB\Client')
                ->expects('fromDSN')
                ->withAnyArgs()
                ->once()
                ->andReturn($mockDatabase)
                ->getMock());
        };
    },
]);
