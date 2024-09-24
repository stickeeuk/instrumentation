<?php

namespace Stickee\Instrumentation\Laravel\Providers;

use Illuminate\Support\ServiceProvider;
use Stickee\Instrumentation\Exporters\Events\InfluxDb;
use Stickee\Instrumentation\Laravel\Config;

/**
 * Instrumentation service provider
 */
class InfluxDbServiceProvider extends ServiceProvider
{
    /**
     * The config
     *
     * @var \Stickee\Instrumentation\Laravel\Config $config
     */
    private Config $config;

    /**
     * Register the service provider
     */
    public function register(): void
    {
        $this->config = $this->app->make(Config::class);

        $this->app->when(InfluxDb::class)
            ->needs('$url')
            ->give(fn () => $this->config->influxDb('url'));

        $this->app->when(InfluxDb::class)
            ->needs('$token')
            ->give(fn () => $this->config->influxDb('token'));

        $this->app->when(InfluxDb::class)
            ->needs('$bucket')
            ->give(fn () => $this->config->influxDb('bucket'));

        $this->app->when(InfluxDb::class)
            ->needs('$org')
            ->give(fn () => $this->config->influxDb('org'));

        $this->app->when(InfluxDb::class)
            ->needs('$verifySsl')
            ->give(fn () => $this->config->influxDb('verify_ssl'));
    }
}
