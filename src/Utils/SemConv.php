<?php

namespace Stickee\Instrumentation\Utils;

class SemConv
{
    // general

    public const STATUS = 'status';

    // HTTP server request duration

    public const HTTP_SERVER_REQUEST_DURATION_NAME = 'http.server.request.duration';

    public const HTTP_SERVER_REQUEST_DURATION_UNIT = 's';

    public const HTTP_SERVER_REQUEST_DURATION_DESCRIPTION = 'Duration of HTTP server requests.';

    public const HTTP_SERVER_REQUEST_DURATION_BUCKETS = [0.1, 0.2, 0.5, 1, 2, 5, 10, 30, 100];

    // memory usage

    public const PROCESS_MEMORY_USAGE_NAME = 'process.memory.usage';

    public const PROCESS_MEMORY_USAGE_UNIT = 'MiB';

    public const PROCESS_MEMORY_USAGE_DESCRIPTION = 'Peak memory usage.';

    public const PROCESS_MEMORY_USAGE_BUCKETS = [8, 16, 32, 64, 128, 256, 512, 1024];

    // database queries

    public const DB_QUERIES_NAME = 'db.queries';

    public const DB_QUERIES_UNIT = '';

    public const DB_QUERIES_DESCRIPTION = 'Total database queries.';

    public const DB_QUERIES_BUCKETS = [8, 16, 32, 64, 128, 256, 512, 1024];

    // job

    public const JOB_NAME = 'job.name';

    public const JOB_QUEUE = 'job.queue';

    public const JOB_STATUS_PROCESSED = 'processed';

    public const JOB_STATUS_FAILED = 'failed';

    // job queues

    public const JOB_QUEUE_LENGTH_NAME = 'job.queue.length';

    public const JOB_QUEUE_AVAILABLE_LENGTH_NAME = 'job.queue.available.length';

    // jobs queued

    public const JOBS_QUEUED_NAME = 'jobs.queued';

    // jobs processed

    public const JOBS_PROCESSED_NAME = 'jobs.processed';

    // jobs start duration

    public const JOB_START_DURATION_NAME = 'job.start.duration';

    public const JOB_START_DURATION_UNIT = 's';

    public const JOB_START_DURATION_DESCRIPTION = 'Time between job being dispatched and starting processing.';

    public const JOB_START_DURATION_BUCKETS = [1, 2, 5, 10, 30, 60, 120, 600];

    // jobs duration

    public const JOB_DURATION_NAME = 'job.duration';

    public const JOB_DURATION_UNIT = 's';

    public const JOB_DURATION_DESCRIPTION = 'Time taken to process a job.';

    public const JOB_DURATION_BUCKETS = [1, 2, 5, 10, 30, 60, 300];
}
