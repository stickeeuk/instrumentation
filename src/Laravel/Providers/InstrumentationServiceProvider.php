<?php

namespace Stickee\Instrumentation\Laravel\Providers;

use Exception;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\ServiceProvider;
use Stickee\Instrumentation\Exporters\Events\LogFile;
use Stickee\Instrumentation\Exporters\Exporter;
use Stickee\Instrumentation\Laravel\Config;
use Stickee\Instrumentation\Laravel\Facades\Instrument;
use Stickee\Instrumentation\Laravel\Http\Middleware\InstrumentationResponseTimeMiddleware;
use Stickee\Instrumentation\Queue\Connectors\BeanstalkdConnector;
use Stickee\Instrumentation\Queue\Connectors\DatabaseConnector;
use Stickee\Instrumentation\Queue\Connectors\NullConnector;
use Stickee\Instrumentation\Queue\Connectors\RedisConnector;
use Stickee\Instrumentation\Queue\Connectors\SqsConnector;
use Stickee\Instrumentation\Queue\Connectors\SyncConnector;

/**
 * Instrumentation service provider
 */
class InstrumentationServiceProvider extends ServiceProvider
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

        $this->mergeConfigFrom(
            __DIR__ . '/../../../config/instrumentation.php', 'instrumentation'
        );

        $this->app->when(LogFile::class)
            ->needs('$filename')
            ->give(fn () => $this->config->logFile('filename'));

        $this->app->bind(Exporter::class, function(Application $app) {
            $eventsExporter = $app->make($this->config->eventsExporterClass());
            $spansExporter = $app->make($this->config->spansExporterClass());

            return new Exporter($eventsExporter, $spansExporter);
        });

        $this->app->singleton('instrument', function(Application $app) {
            $exporter = $app->make(Exporter::class);
            $exporter->setErrorHandler(function (Exception $e) {
                Log::error($e->getMessage());
            });

            return $exporter;
        });

        $this->app->extend('queue', function (QueueManager $manager) {
            $manager->addConnector('beanstalkd', fn (): BeanstalkdConnector => new BeanstalkdConnector());
            $manager->addConnector('database', fn (): DatabaseConnector => new DatabaseConnector($this->app['db']));
            $manager->addConnector('null', fn (): NullConnector => new NullConnector());
            $manager->addConnector('redis', fn (): RedisConnector => new RedisConnector($this->app['redis']));
            $manager->addConnector('sqs', fn (): SqsConnector => new SqsConnector());
            $manager->addConnector('sync', fn (): SyncConnector => new SyncConnector());

            return $manager;
        });
    }

    /**
     * Bootstrap any application services
     */
    public function boot(): void
    {
        if (!$this->config->enabled()) {
            return;
        }

        Schedule::call(function () {
            foreach ($this->config->queueNames() as $queueName) {
                Instrument::gauge('queue_length', ['queue' => $queueName], Queue::size($queueName));
                Instrument::gauge('queue_available_length', ['queue' => $queueName], Queue::availableSize($queueName));
            }

            app('instrument')->flush();
        })->everyFifteenSeconds();

        // Flush events when a command finishes
        Event::listen(CommandFinished::class, function () {
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

        if ($this->app->runningUnitTests()) {
            return;
        }

        if ($this->config->responseTimeMiddlewareEnabled()) {
            $this->registerResponseTimeMiddleware();
        }

        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__ . '/../../../config/instrumentation.php' => config_path('instrumentation.php'),
        ]);
    }

    /**
     * Register the response time middleware
     */
    private function registerResponseTimeMiddleware(): void
    {
        // We attach to the HttpKernel, so we need it to be available.
        if (!$this->app->bound(Kernel::class)) {
            return;
        }

        /** @var \use Illuminate\Contracts\Http\Kernel $httpKernel */
        $httpKernel = $this->app->make(Kernel::class);

        $httpKernel->prependMiddleware(InstrumentationResponseTimeMiddleware::class);
    }
}
