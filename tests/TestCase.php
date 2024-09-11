<?php

declare(strict_types=1);

namespace Stickee\Instrumentation\Tests;

use Illuminate\Encryption\Encrypter;
use Illuminate\Foundation\Testing\WithFaker;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Stickee\Instrumentation\Laravel\Providers\InfluxDbServiceProvider;
use Stickee\Instrumentation\Laravel\Providers\InstrumentationServiceProvider;
use Stickee\Instrumentation\Laravel\Providers\OpenTelemetryServiceProvider;

/**
 * Class TestCase
 */
abstract class TestCase extends OrchestraTestCase
{
    use WithFaker;

    /**
     * Example DSN for use in tests.
     *
     * @var string
     */
    public const EXAMPLE_DSN = 'https+influxdb://username:password@localhost:8086/databasename';

    /** @inheritDoc */
    protected function getPackageProviders($app): array
    {
        return [
            InfluxDbServiceProvider::class,
            InstrumentationServiceProvider::class,
            OpenTelemetryServiceProvider::class,
        ];
    }

    /** @inheritDoc */
    protected function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        config()->set('app.key', 'base64:' . base64_encode(
            Encrypter::generateKey($app['config']['app.cipher'])
        ));
    }
}
