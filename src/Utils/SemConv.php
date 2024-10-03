<?php

namespace Stickee\Instrumentation\Utils;

class SemConv
{
    // general

    const STATUS = 'status';

    // HTTP server request duration

    const HTTP_SERVER_REQUEST_DURATION_NAME = 'http.server.request.duration';

    const HTTP_SERVER_REQUEST_DURATION_UNIT = 's';

    const HTTP_SERVER_REQUEST_DURATION_DESCRIPTION = 'Duration of HTTP server requests.';

    const HTTP_SERVER_REQUEST_DURATION_BUCKETS = [ 0.1, 0.2, 0.5, 1, 2, 5, 10, 30, 100 ];

    // jobs

    const JOB_NAME = 'job.name';

    const JOB_QUEUE = 'job.queue';

    const JOB_STATUS_PROCESSED = 'processed';

    const JOB_STATUS_FAILED = 'failed';

    // jobs queued

    const JOBS_QUEUED_NAME = 'jobs.queued';

    // jobs processed

    const JOBS_PROCESSED_NAME = 'jobs.processed';

    // jobs start duration

    const JOB_START_DURATION_NAME = 'job.start.duration';

    const JOB_START_DURATION_UNIT = 's';

    const JOB_START_DURATION_DESCRIPTION = 'Time between job being dispatched and starting processing.';

    const JOB_START_DURATION_BUCKETS = [ 1, 2, 5, 10, 30, 60, 120, 600 ];

    // jobs duration

    const JOB_DURATION_NAME = 'job.duration';

    const JOB_DURATION_UNIT = 's';

    const JOB_DURATION_DESCRIPTION = 'Time taken to process a job.';

    const JOB_DURATION_BUCKETS = [ 1, 2, 5, 10, 30, 60, 300 ];
}
