<?php

namespace Stickee\Instrumentation\Queue\Connectors;

use Illuminate\Queue\Connectors\DatabaseConnector as IlluminateDatabaseConnector;
use Illuminate\Support\Facades\DB;
use Stickee\Instrumentation\Queue\DatabaseQueueWithAvailableSize;

class DatabaseConnector extends IlluminateDatabaseConnector
{
    /** @inheritDoc */
    public function connect(array $config)
    {
        return new DatabaseQueueWithAvailableSize(
            DB::connection(),
            $config['table'],
            $config['queue'],
            $config['retry_after'] ?? 60,
            $config['after_commit'] ?? null
        );
    }
}
