<?php

declare(strict_types=1);

namespace Stickee\Instrumentation\Tests;

use Illuminate\Encryption\Encrypter;
use Illuminate\Foundation\Testing\WithFaker;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Stickee\Instrumentation\Laravel\ServiceProvider;

/**
 * Class TestCase
 */
abstract class TestCase extends OrchestraTestCase

{
    use WithFaker;

    /** @inheritDoc */
    protected function getPackageProviders($app): array
    {
        return [
            ServiceProvider::class,
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
