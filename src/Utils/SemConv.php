<?php

namespace Stickee\Instrumentation\Utils;

class SemConv
{
    // HTTP server request duration

    const HTTP_SERVER_REQUEST_DURATION_NAME = 'http.server.request.duration';

    const HTTP_SERVER_REQUEST_DURATION_UNIT = 's';

    const HTTP_SERVER_REQUEST_DURATION_DESCRIPTION = 'Duration of HTTP server requests.';

    const HTTP_SERVER_REQUEST_DURATION_BUCKETS = [ 0.1, 0.2, 0.5, 1, 2, 5, 10, 30, 100 ];

    // jobs

    const JOB_NAME = 'job.name';

    const JOB_QUEUE = 'job.queue';
}
