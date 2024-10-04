<?php

namespace Stickee\Instrumentation\Utils;

class SemConv
{
    const HTTP_SERVER_REQUEST_DURATION_NAME = 'http.server.request.duration';

    const HTTP_SERVER_REQUEST_DURATION_UNIT = 's';

    const HTTP_SERVER_REQUEST_DURATION_DESCRIPTION = 'Duration of HTTP server requests.';

    const HTTP_SERVER_REQUEST_DURATION_BUCKETS = [ 0.1, 0.2, 0.5, 1, 2, 5, 10, 30, 100 ];

    const PROCESS_MEMORY_USAGE_NAME = 'process.memory.usage';

    const PROCESS_MEMORY_USAGE_UNIT = 'MiB';

    const PROCESS_MEMORY_USAGE_DESCRIPTION = 'Peak memory usage.';

    const PROCESS_MEMORY_USAGE_BUCKETS = [ 8, 16, 32, 64, 128, 256, 512, 1024 ];

    const DB_QUERIES_NAME = 'db.queries.total';

    const DB_QUERIES_UNIT = '';

    const DB_QUERIES_DESCRIPTION = 'Total database queries.';

    const DB_QUERIES_BUCKETS = [ 8, 16, 32, 64, 128, 256, 512, 1024 ];
}
