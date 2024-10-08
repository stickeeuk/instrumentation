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
use Illuminate\Support\Facades\DB;
use OpenTelemetry\Contrib\Instrumentation\Laravel\Watchers\Watcher;
use Stickee\Instrumentation\Laravel\Facades\Instrument;
use Stickee\Instrumentation\Utils\SemConv;

/**
 * Record the number of queries
 */
class QueryCountWatcher extends Watcher
{
    private $totalQueries = 0;

    /**
     * Register the watcher.
     */
    public function register(Application $app): void
    {
        DB::listen(fn() => $this->totalQueries++);

        $app['events']->listen(JobProcessing::class, fn() => $this->totalQueries = 0);
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

        $app['events']->listen(ScheduledTaskStarting::class, fn() => $this->totalQueries = 0);
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

        $app['events']->listen(CommandStarting::class, fn() => $this->totalQueries = 0);
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
            SemConv::DB_QUERIES_NAME,
            SemConv::DB_QUERIES_UNIT,
            SemConv::DB_QUERIES_DESCRIPTION,
            SemConv::DB_QUERIES_BUCKETS,
            $this->totalQueries,
            $attributes
        );
    }
}
