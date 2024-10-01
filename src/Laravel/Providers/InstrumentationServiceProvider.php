<?php

namespace Stickee\Instrumentation\Laravel\Providers;

use Exception;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Contracts\Http\Kernel as KernelInterface;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobQueued;
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
use Stickee\Instrumentation\Utils\SemConv;

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

        Queue::createPayloadUsing(fn ($connectionName, $queue, $payload) => [...$payload, 'created_at' => now()]);

        Queue::before(function (JobProcessing $event) {
            if (isset($event->job->payload()['created_at'])) {
                Instrument::histogram(
                    'job_start_duration',
                    's',
                    'Time between job being dispatched and starting processing.',
                    [1, 2, 5, 10, 30, 60, 120, 600],
                    now()->diffInSeconds(date: $event->job->payload()['created_at'], absolute: true),
                    [
                        SemConv::JOB_NAME => $event->job->resolveName(),
                        SemConv::JOB_QUEUE => $event->job->getQueue(),
                    ]
                );
            }
        });

        Event::listen(JobQueued::class, function ($event) {
            Instrument::counter('jobs_queued', [
                SemConv::JOB_NAME => $event->job->resolveName(),
                SemConv::JOB_QUEUE => $event->job->getQueue(),
            ]);
        });

        Event::listen(JobProcessed::class, function ($event) {
            Instrument::counter('jobs_processed', [
                SemConv::JOB_NAME => $event->job->resolveName(),
                SemConv::JOB_QUEUE => $event->job->getQueue(),
            ]);
            if (isset($event->job->payload()['created_at'])) {
                Instrument::histogram(
                    'job_duration',
                    's',
                    'Time taken to process a job.',
                    [0.1, 0.2, 0.5, 1, 2, 5, 10, 30, 60],
                    now()->diffInSeconds(date: $event->job->payload()['created_at'], absolute: true),
                    [
                        SemConv::JOB_NAME => $event->job->resolveName(),
                        SemConv::JOB_QUEUE => $event->job->getQueue(),
                    ]
                );
            }
        });

        Event::listen(JobFailed::class, function ($event) {
            Instrument::counter('jobs_failed', [
                SemConv::JOB_NAME => $event->job->resolveName(),
                SemConv::JOB_QUEUE => $event->job->getQueue(),
            ]);
        });

        if ($this->config->responseTimeMiddlewareEnabled()) {
            $this->registerResponseTimeMiddleware();
        }

        // Flush events when a command finishes
        Event::listen(CommandFinished::class, fn () => app('instrument')->flush());

        // Flush events when a queue job completes
        Queue::after(fn () => app('instrument')->flush());

        // Flush events when a queue job fails
        Queue::failing(fn () => app('instrument')->flush());

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
        if ($this->app->bound(KernelInterface::class)) {
            /** @var Illuminate\Foundation\Http\Kernel $httpKernel */
            $httpKernel = $this->app->make(KernelInterface::class);

            if ($httpKernel instanceof Kernel) {
                $httpKernel->prependMiddleware(InstrumentationResponseTimeMiddleware::class);
            }
        }
    }
}
