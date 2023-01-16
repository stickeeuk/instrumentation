<?php

namespace Stickee\Instrumentation\Laravel;

use Exception;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Stickee\Instrumentation\Databases\DatabaseInterface;
use Stickee\Instrumentation\Databases\InfluxDb;
use Stickee\Instrumentation\Databases\Log as LogDatabase;

/**
 * Instrumentation service provider
 */
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

        $this->app->when(InfluxDb::class)
            ->needs('$dsn')
            ->give(function () {
                $value = config('instrumentation.dsn');

                if (empty($value)) {
                    throw new Exception('Config variable `instrumentation.dsn` not set');
                }

                return $value;
            });

        $this->app->when(InfluxDb::class)
            ->needs('$verifySsl')
            ->give(function () {
                return config('instrumentation.verifySsl', true);
            });

        $this->app->when(LogDatabase::class)
            ->needs('$filename')
            ->give(function() {
                $value = config('instrumentation.filename');

                if (empty($value)) {
                    throw new Exception('Config variable `instrumentation.filename` not set');
                }

                return $value;
            });

        $this->app->singleton('instrument', function(Application $app) {
            $class = config('instrumentation.database');

            if (empty($class)) {
                throw new Exception('Config variable `instrumentation.database` not set');
            }

            if (!class_exists($class, true)) {
                throw new Exception('Config variable `instrumentation.database` class not found: ' . $class);
            }

            if (!is_a($class, DatabaseInterface::class, true)) {
                throw new Exception('Config variable `instrumentation.database` does not implement \Stickee\Instrumentation\Databases\DatabaseInterface: ' . $class);
            }

            $database = $app->make($class);
            $database->setErrorHandler(function (Exception $e) {
                Log::error($e->getMessage());
            });

            return $database;
        });
    }

    /**
     * Bootstrap any application services
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../config/instrumentation.php' => config_path('instrumentation.php'),
        ]);

        // Flush events when a command finishes
        Event::listen('Illuminate\Console\Events\CommandFinished', function () {
            app('instrument')->flush();
        });

        // Flush events when a queue job completes
        Queue::after(function () {
            app('instrument')->flush();
        });

        // Flush events when a queue job fails
        Queue::failing(function () {
            app('instrument')->flush();
        });
    }
}
