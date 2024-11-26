<?php

namespace Stickee\Instrumentation\Laravel\Providers;

use Exception;
use function OpenTelemetry\Instrumentation\hook;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Contracts\Http\Kernel as KernelInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\ServiceProvider;
use OpenTelemetry\Contrib\Instrumentation\Laravel\Watchers\LogWatcher;
use OpenTelemetry\SDK\SdkAutoloader;
use Stickee\Instrumentation\DataScrubbers\ConfigDataScrubber;
use Stickee\Instrumentation\DataScrubbers\DataScrubberInterface;
use Stickee\Instrumentation\DataScrubbers\MultiDataScrubber;
use Stickee\Instrumentation\DataScrubbers\NullDataScrubber;
use Stickee\Instrumentation\DataScrubbers\RegexDataScrubber;
use Stickee\Instrumentation\Exporters\Events\OpenTelemetry as OpenTelemetryEvents;
use Stickee\Instrumentation\Exporters\Exporter;
use Stickee\Instrumentation\Exporters\Spans\OpenTelemetry as OpenTelemetrySpans;
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
use Stickee\Instrumentation\Watchers\MemoryWatcher;
use Stickee\Instrumentation\Watchers\QueryCountWatcher;
use Throwable;

/**
 * Format the parameters for the logger.
 * From \Illuminate\Log\Logger::formatMessage
 *
 * @param mixed $value The value to convert to a string
 */
function toString(mixed $value): string
{
    try {
        if (is_array($value)) {
            return (string) var_export($value, true);
        } elseif ($value instanceof Jsonable) {
            return (string) $value->toJson();
        } elseif ($value instanceof Arrayable) {
            return (string) var_export($value->toArray(), true);
        }

        return (string) $value;
    } catch (Throwable) {
        // Do nothing
    }

    return 'Non-stringable value';
}

/**
 * Instrumentation service provider
 */
class InstrumentationServiceProvider extends ServiceProvider
{
    /**
     * Whether the hooks have been added
     */
    private static $hooked = false;

    /**
     * The config
     */
    private Config $config;

    /**
     * Register the service provider
     */
    #[\Override]
    public function register(): void
    {
        $this->config = $this->app->make(Config::class);

        $this->mergeConfigFrom(
            __DIR__ . '/../../../config/instrumentation.php',
            'instrumentation'
        );

        $this->app->bind(Exporter::class, function (Application $app): \Stickee\Instrumentation\Exporters\Exporter {
            $eventsExporter = $app->make(OpenTelemetryEvents::class);
            $spansExporter = $app->make(OpenTelemetrySpans::class);

            return new Exporter($eventsExporter, $spansExporter, $app->make(DataScrubberInterface::class));
        });

        $this->app->singleton('instrument', function (Application $app) {
            $exporter = $app->make(Exporter::class);
            $exporter->setErrorHandler(function (Exception $e): void {
                Log::error($e->getMessage());
            });

            return $exporter;
        });

        // Extend the queue connectors to add availableCount()
        $this->app->extend('queue', function (QueueManager $manager): \Illuminate\Queue\QueueManager {
            $manager->addConnector('beanstalkd', fn(): BeanstalkdConnector => new BeanstalkdConnector());
            $manager->addConnector('database', fn(): DatabaseConnector => new DatabaseConnector($this->app['db']));
            $manager->addConnector('null', fn(): NullConnector => new NullConnector());
            $manager->addConnector('redis', fn(): RedisConnector => new RedisConnector($this->app['redis']));
            $manager->addConnector('sqs', fn(): SqsConnector => new SqsConnector());
            $manager->addConnector('sync', fn(): SyncConnector => new SyncConnector());

            return $manager;
        });

        $this->app->bind(DataScrubberInterface::class, function (Application $app): DataScrubberInterface {
            $dataScrubbers = [];

            if (! empty(config('instrumentation.scrubbing.regexes'))) {
                $dataScrubbers[] = new RegexDataScrubber(config('instrumentation.scrubbing.regexes'));
            }

            if (! empty(config('instrumentation.scrubbing.config_key_regexes'))) {
                $dataScrubbers[] = new ConfigDataScrubber(
                    config('instrumentation.scrubbing.config_key_regexes'),
                    config('instrumentation.scrubbing.config_key_ignore_regexes')
                );
            }

            if (empty($dataScrubbers)) {
                return new NullDataScrubber();
            }

            return new MultiDataScrubber($dataScrubbers);
        });

        $this->hook();
    }

    /**
     * Bootstrap any application services
     */
    public function boot(): void
    {
        if (! SdkAutoloader::isEnabled()) {
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

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__ . '/../../../config/instrumentation.php' => config_path('instrumentation.php'),
        ]);
    }

    /**
     * Add extra hooks
     */
    private function hook(): void
    {
        if (self::$hooked) {
            return;
        }

        self::$hooked = true;

        $scrubber = app(DataScrubberInterface::class);

        // Hook in to the opentelemetry-auto-laravel LogWatcher to scrub data
        hook(
            LogWatcher::class,
            'recordLog',
            pre: function (LogWatcher $watcher, array $params, string $class, string $function, ?string $filename, ?int $lineno) use ($scrubber): array {
                // TODO: can we do this a better way?
                if (app()->runningUnitTests()) {
                    $scrubber = app(DataScrubberInterface::class);
                }

                set_error_handler(function(int $errNo, string $errStr) {
                    throw new Exception($errStr, $errNo);
                });

                try {
                    $maxLength = (int) config('instrumentation.scrubbing.max_length', 10240);
                    $message = $params[0];

                    $message->message = $scrubber->scrub('', mb_substr(toString($message->message), 0, $maxLength));

                    foreach ($message->context as $key => $value) {
                        $message->context[$key] = $scrubber->scrub((string) $key, mb_substr(toString($value), 0, $maxLength));
                    }
                } catch (Throwable) {
                    // Ignore errors
                }

                restore_error_handler();

                return $params;
            },
        );
    }

    /**
     * Instrument jobs
     */
    private function instrumentJobs(): void
    {
        Queue::createPayloadUsing(fn($connectionName, $queue, $payload) => [...$payload, 'created_at' => now()]);

        Event::listen(JobQueued::class, function ($event): void {
            Instrument::counter(SemConv::JOBS_QUEUED_NAME, [
                SemConv::JOB_NAME => $event->payload()['displayName'],
                SemConv::JOB_QUEUE => $event->job->queue,
            ]);
        });

        $startTime = null;

        Queue::before(function (JobProcessing $event) use (&$startTime): void {
            $startTime = now();

            if (isset($event->job->payload()['created_at'])) {
                Instrument::histogram(
                    SemConv::JOB_START_DURATION_NAME,
                    SemConv::JOB_START_DURATION_UNIT,
                    SemConv::JOB_START_DURATION_DESCRIPTION,
                    SemConv::JOB_START_DURATION_BUCKETS,
                    [
                        SemConv::JOB_NAME => $event->job->resolveName(),
                        SemConv::JOB_QUEUE => $event->job->getQueue(),
                    ],
                    now()->diffInSeconds(date: $event->job->payload()['created_at'], absolute: true),
                );
            }
        });

        Event::listen(JobProcessed::class, function ($event) use (&$startTime): void {
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
                [
                    SemConv::JOB_NAME => $event->job->resolveName(),
                    SemConv::JOB_QUEUE => $event->job->getQueue(),
                    SemConv::STATUS => SemConv::JOB_STATUS_PROCESSED,
                ],
                now()->diffInSeconds(date: $startTime, absolute: true),
            );
        });

        Event::listen(JobFailed::class, function ($event) use (&$startTime): void {
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
                [
                    SemConv::JOB_NAME => $event->job->resolveName(),
                    SemConv::JOB_QUEUE => $event->job->getQueue(),
                    SemConv::STATUS => SemConv::JOB_STATUS_FAILED,
                ],
                now()->diffInSeconds(date: $startTime, absolute: true),
            );
        });
    }

    /**
     * Instrument job queues
     */
    private function instrumentJobQueues(): void
    {
        if (! $this->app->runningUnitTests()) {
            Schedule::call(function (): void {
                foreach ($this->config->queueNames() as $queueName) {
                    Instrument::gauge(
                        SemConv::JOB_QUEUE_LENGTH_NAME,
                        [
                            SemConv::JOB_QUEUE => $queueName,
                        ],
                        Queue::size($queueName)
                    );
                    Instrument::gauge(
                        SemConv::JOB_QUEUE_AVAILABLE_LENGTH_NAME,
                        [
                            SemConv::JOB_QUEUE => $queueName,
                        ],
                        Queue::availableSize($queueName) // @phpstan-ignore staticMethod.notFound
                    );
                }

                app('instrument')->flush();
            })->everyFifteenSeconds();
        }
    }

    /**
     * Register the flush events
     */
    private function registerFlushEvents(): void
    {
        Event::listen(CommandFinished::class, fn() => app('instrument')->flush());

        Queue::after(fn() => app('instrument')->flush());

        Queue::failing(fn() => app('instrument')->flush());
    }

    /**
     * Register the response time middleware
     */
    private function registerResponseTimeMiddleware(): void
    {
        if ($this->app->bound(KernelInterface::class)) {
            /** @var \Illuminate\Foundation\Http\Kernel $httpKernel */
            $httpKernel = $this->app->make(KernelInterface::class);

            if ($httpKernel instanceof Kernel) {
                $httpKernel->prependMiddleware(InstrumentationResponseTimeMiddleware::class);
            }
        }
    }
}
