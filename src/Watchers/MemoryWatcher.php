<?php

namespace Stickee\Instrumentation\Watchers;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Routing\Events\ResponsePrepared;
use OpenTelemetry\Contrib\Instrumentation\Laravel\Watchers\Watcher;
use Stickee\Instrumentation\Laravel\Facades\Instrument;
use Stickee\Instrumentation\Utils\SemConv;

/**
 * Record the memory usage
 */
class MemoryWatcher extends Watcher
{
    /**
     * Register the watcher.
     */
    public function register(Application $app): void
    {
        $app['events']->listen(JobProcessing::class, fn() => memory_reset_peak_usage());
        $app['events']->listen(JobProcessed::class, function (JobProcessed $event): void {
            $this->recordQueries([
                'type' => 'job',
                'queue' => $event->job->getQueue(),
                'job' => get_class($event->job),
            ]);
        });
        $app['events']->listen(JobExceptionOccurred::class, function (JobExceptionOccurred $event): void {
            $this->recordQueries([
                'type' => 'job',
                'queue' => $event->job->getQueue(),
                'job' => get_class($event->job),
            ]);
        });

        $app['events']->listen(ScheduledTaskStarting::class, fn() => memory_reset_peak_usage());
        $app['events']->listen(ScheduledTaskFinished::class, function (ScheduledTaskFinished $event): void {
            $this->recordQueries([
                'type' => 'scheduled_task',
                'task' => get_class($event->task),
            ]);
        });
        $app['events']->listen(ScheduledTaskFailed::class, function (ScheduledTaskFailed $event): void {
            $this->recordQueries([
                'type' => 'scheduled_task',
                'task' => get_class($event->task),
            ]);
        });

        $app['events']->listen(CommandStarting::class, fn() => memory_reset_peak_usage());
        $app['events']->listen(CommandFinished::class, function (CommandFinished $event): void {
            if (in_array($event->command, ['schedule:run', 'queue:work'])) {
                return;
            }

            $this->recordQueries([
                'type' => 'command',
                'command' => $event->command,
            ]);
        });

        $app['events']->listen(ResponsePrepared::class, function (ResponsePrepared $event): void {
            $this->recordQueries([
                'type' => 'request',
                'http.request.method' => request()->method(),
                'http.route' => request()->path(),
            ]);
        });
    }

    /**
     * Record queries
     *
     * @param array $attributes Attributes
     */
    private function recordQueries(array $attributes): void
    {
        Instrument::histogram(
            SemConv::PROCESS_MEMORY_USAGE_NAME,
            SemConv::PROCESS_MEMORY_USAGE_UNIT,
            SemConv::PROCESS_MEMORY_USAGE_DESCRIPTION,
            SemConv::PROCESS_MEMORY_USAGE_BUCKETS,
            memory_get_peak_usage() / 1024 / 1024,
            $attributes
        );
    }
}
