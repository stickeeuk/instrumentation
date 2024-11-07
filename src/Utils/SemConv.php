<?php

namespace Stickee\Instrumentation\Utils;

class SemConv
{
    // General

    public const string STATUS = 'status';

    // HTTP

    public const string HTTP_REQUEST_METHOD = 'http.request.method';

    public const string HTTP_RESPONSE_STATUS_CODE = 'http.response.status_code';

    public const string HTTP_ROUTE = 'http.route';

    // HTTP server request duration

    public const string HTTP_SERVER_REQUEST_DURATION_NAME = 'http.server.request.duration';

    public const string HTTP_SERVER_REQUEST_DURATION_UNIT = 's';

    public const string HTTP_SERVER_REQUEST_DURATION_DESCRIPTION = 'Duration of HTTP server requests.';

    /** @var array<int, float|int> */
    public const array HTTP_SERVER_REQUEST_DURATION_BUCKETS = [0.1, 0.2, 0.5, 1, 2, 5, 10, 30, 100];

    // Memory usage

    public const string PROCESS_MEMORY_USAGE_NAME = 'process.memory.usage';

    public const string PROCESS_MEMORY_USAGE_UNIT = 'MiB';

    public const string PROCESS_MEMORY_USAGE_DESCRIPTION = 'Peak memory usage.';

    /** @var array<int, int> */
    public const array PROCESS_MEMORY_USAGE_BUCKETS = [8, 16, 32, 64, 128, 256, 512, 1024];

    // Database queries

    public const string DB_QUERIES_NAME = 'db.queries';

    public const string DB_QUERIES_UNIT = '';

    public const string DB_QUERIES_DESCRIPTION = 'Total database queries.';

    /** @var array<int, int> */
    public const array DB_QUERIES_BUCKETS = [8, 16, 32, 64, 128, 256, 512, 1024];

    // Jobs

    public const string JOB_NAME = 'job.name';

    public const string JOB_QUEUE = 'job.queue';

    public const string JOB_STATUS_PROCESSED = 'processed';

    public const string JOB_STATUS_FAILED = 'failed';

    // Job queues

    public const string JOB_QUEUE_LENGTH_NAME = 'job.queue.length';

    public const string JOB_QUEUE_AVAILABLE_LENGTH_NAME = 'job.queue.available.length';

    // Jobs queued

    public const string JOBS_QUEUED_NAME = 'jobs.queued';

    // Jobs processed

    public const string JOBS_PROCESSED_NAME = 'jobs.processed';

    // Jobs start duration

    public const string JOB_START_DURATION_NAME = 'job.start.duration';

    public const string JOB_START_DURATION_UNIT = 's';

    public const string JOB_START_DURATION_DESCRIPTION = 'Time between job being dispatched and starting processing.';

    /** @var array<int, int> */
    public const array JOB_START_DURATION_BUCKETS = [1, 2, 5, 10, 30, 60, 120, 600];

    // Jobs duration

    public const string JOB_DURATION_NAME = 'job.duration';

    public const string JOB_DURATION_UNIT = 's';

    public const string JOB_DURATION_DESCRIPTION = 'Time taken to process a job.';

    /** @var array<int, int> */
    public const array JOB_DURATION_BUCKETS = [1, 2, 5, 10, 30, 60, 300];
}
