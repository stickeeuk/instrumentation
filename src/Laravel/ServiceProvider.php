<?php

namespace Stickee\Instrumentation\Laravel;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Stickee\Instrumentation\Instrument;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Register the service provider
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/instrumentation.php', 'instrumentation'
        );

        if (config('instrumentation.dsn')) {
            $this->app->singleton('instrument', function() {
                $class = config('instrumentation.database');

                return new $class(config('instrumentation.dsn'));
            });
        }
    }

    /**
     * Bootstrap any application services
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../config/instrumentation.php' => config_path('instrumentation.php'),
        ]);
    }
}
