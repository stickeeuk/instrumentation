<?php

namespace Stickee\Instrumentation\Laravel\Providers;

use Exception;
use function OpenTelemetry\Instrumentation\hook;
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
use OpenTelemetry\Contrib\Instrumentation\Laravel\Watchers\LogWatcher;
use Stickee\Instrumentation\DataScrubbers\DataScrubberInterface;
use Stickee\Instrumentation\DataScrubbers\DefaultDataScrubber;
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
use Stickee\Instrumentation\Watchers\MemoryWatcher;
use Stickee\Instrumentation\Watchers\QueryCountWatcher;
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

            return new Exporter($eventsExporter, $spansExporter, $app->make(DataScrubberInterface::class));
        });

        $this->app->singleton('instrument', function(Application $app) {
            $exporter = $app->make(Exporter::class);
            $exporter->setErrorHandler(function (Exception $e) {
                Log::error($e->getMessage());
            });

            return $exporter;
        });

        // Extend the queue connectors to add availableCount()
        $this->app->extend('queue', function (QueueManager $manager) {
            $manager->addConnector('beanstalkd', fn (): BeanstalkdConnector => new BeanstalkdConnector());
            $manager->addConnector('database', fn (): DatabaseConnector => new DatabaseConnector($this->app['db']));
            $manager->addConnector('null', fn (): NullConnector => new NullConnector());
            $manager->addConnector('redis', fn (): RedisConnector => new RedisConnector($this->app['redis']));
            $manager->addConnector('sqs', fn (): SqsConnector => new SqsConnector());
            $manager->addConnector('sync', fn (): SyncConnector => new SyncConnector());

            return $manager;
        });

        $this->app->bind(DataScrubberInterface::class, DefaultDataScrubber::class);

        // Hook in to the opentelemetry-auto-laravel LogWatcher to scrub data
        hook(
            LogWatcher::class,
            'recordLog',
            pre: function (LogWatcher $watcher, array $params, string $class, string $function, ?string $filename, ?int $lineno): array {
                $scrubber = app(DataScrubberInterface::class);
                $message = $params[0];
                $message->message = $scrubber->scrub('', $message->message);

                foreach ($message->context as $key => $value) {
                    $message->context[$key] = $scrubber->scrub($key, $value);
                }

                return $params;
            },
        );
    }

    /**
     * Bootstrap any application services
     */
    public function boot(): void
    {
        if (!$this->config->enabled()) {
            return;
        }

        $this->instrumentJobs();
        $this->instrumentJobQueues();

        (new MemoryWatcher())->register($this->app);
        (new QueryCountWatcher())->register($this->app);

        if ($this->config->responseTimeMiddlewareEnabled()) {
            $this->registerResponseTimeMiddleware();
        }

        $this->registerFlushEvents();

        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__ . '/../../../config/instrumentation.php' => config_path('instrumentation.php'),
        ]);
    }

    private function instrumentJobs(): void
    {
        Queue::createPayloadUsing(fn ($connectionName, $queue, $payload) => [...$payload, 'created_at' => now()]);

        Event::listen(JobQueued::class, function ($event) {
            Instrument::counter(SemConv::JOBS_QUEUED_NAME, [
                SemConv::JOB_NAME => $event->job->resolveName(),
                SemConv::JOB_QUEUE => $event->job->getQueue(),
            ]);
        });

        $startTime = null;

        Queue::before(function (JobProcessing $event) use (&$startTime) {
            $startTime = now();
            if (isset($event->job->payload()['created_at'])) {
                Instrument::histogram(
                    SemConv::JOB_START_DURATION_NAME,
                    SemConv::JOB_START_DURATION_UNIT,
                    SemConv::JOB_START_DURATION_DESCRIPTION,
                    SemConv::JOB_START_DURATION_BUCKETS,
                    now()->diffInSeconds(date: $event->job->payload()['created_at'], absolute: true),
                    [
                        SemConv::JOB_NAME => $event->job->resolveName(),
                        SemConv::JOB_QUEUE => $event->job->getQueue(),
                    ]
                );
            }
        });

        Event::listen(JobProcessed::class, function ($event) use ($startTime) {
            Instrument::counter(SemConv::JOBS_PROCESSED_NAME, [
                SemConv::JOB_NAME => $event->job->resolveName(),
                SemConv::JOB_QUEUE => $event->job->getQueue(),
                SemConv::STATUS => SemConv::JOB_STATUS_PROCESSED,
            ]);
            Instrument::histogram(
                SemConv::JOB_DURATION_NAME,
                SemConv::JOB_DURATION_UNIT,
                SemConv::JOB_DURATION_DESCRIPTION,
                SemConv::JOB_DURATION_BUCKETS,
                now()->diffInSeconds(date: $startTime, absolute: true),
                [
                    SemConv::JOB_NAME => $event->job->resolveName(),
                    SemConv::JOB_QUEUE => $event->job->getQueue(),
                    SemConv::STATUS => SemConv::JOB_STATUS_PROCESSED,
                ]
            );
        });

        Event::listen(JobFailed::class, function ($event) use ($startTime) {
            Instrument::counter(SemConv::JOBS_PROCESSED_NAME, [
                SemConv::JOB_NAME => $event->job->resolveName(),
                SemConv::JOB_QUEUE => $event->job->getQueue(),
                SemConv::STATUS => SemConv::JOB_STATUS_FAILED,
            ]);
            Instrument::histogram(
                SemConv::JOB_DURATION_NAME,
                SemConv::JOB_DURATION_UNIT,
                SemConv::JOB_DURATION_DESCRIPTION,
                SemConv::JOB_DURATION_BUCKETS,
                now()->diffInSeconds(date: $startTime, absolute: true),
                [
                    SemConv::JOB_NAME => $event->job->resolveName(),
                    SemConv::JOB_QUEUE => $event->job->getQueue(),
                    SemConv::STATUS => SemConv::JOB_STATUS_FAILED,
                ]
            );
        });
    }

    private function instrumentJobQueues(): void
    {
        if (!$this->app->runningUnitTests()) {
            Schedule::call(function () {
                foreach ($this->config->queueNames() as $queueName) {
                    Instrument::gauge(
                        SemConv::JOB_QUEUE_LENGTH_NAME,
                        [
                            SemConv::JOB_QUEUE => $queueName
                        ],
                        Queue::size($queueName)
                    );
                    Instrument::gauge(
                        SemConv::JOB_QUEUE_AVAILABLE_LENGTH_NAME,
                        [
                            SemConv::JOB_QUEUE => $queueName
                        ],
                        Queue::availableSize($queueName)
                    );
                }

                app('instrument')->flush();
            })->everyFifteenSeconds();
        }
    }

    private function registerFlushEvents(): void
    {
        Event::listen(CommandFinished::class, fn () => app('instrument')->flush());

        Queue::after(fn () => app('instrument')->flush());

        Queue::failing(fn () => app('instrument')->flush());
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
