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
    private int $totalQueries = 0;

    /**
     * Register the watcher
     *
     * @param \Illuminate\Contracts\Foundation\Application $app The application
     */
    public function register(Application $app): void
    {
        DB::listen(fn(): int => $this->totalQueries++);

        $app['events']->listen(JobProcessing::class, fn(): int => $this->totalQueries = 0);
        $app['events']->listen(JobProcessed::class, function (JobProcessed $event): void {
            $this->recordQueries([
                'type' => 'job',
                SemConv::JOB_QUEUE => $event->job->getQueue(),
                SemConv::JOB_NAME => $event->job->resolveName(),
            ]);
        });
        $app['events']->listen(JobExceptionOccurred::class, function (JobExceptionOccurred $event): void {
            $this->recordQueries([
                'type' => 'job',
                SemConv::JOB_QUEUE => $event->job->getQueue(),
                SemConv::JOB_NAME => $event->job->resolveName(),
            ]);
        });

        $app['events']->listen(ScheduledTaskStarting::class, fn(): int => $this->totalQueries = 0);
        $app['events']->listen(ScheduledTaskFinished::class, function (ScheduledTaskFinished $event): void {
            $this->recordQueries([
                'type' => 'scheduled_task',
                'task' => $event->task::class,
            ]);
        });
        $app['events']->listen(ScheduledTaskFailed::class, function (ScheduledTaskFailed $event): void {
            $this->recordQueries([
                'type' => 'scheduled_task',
                'task' => $event->task::class,
            ]);
        });

        $app['events']->listen(CommandStarting::class, fn(): int => $this->totalQueries = 0);
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
                SemConv::HTTP_REQUEST_METHOD => request()->method(),
                SemConv::HTTP_ROUTE => request()->path(),
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
            $attributes,
            $this->totalQueries,
        );
    }
}
