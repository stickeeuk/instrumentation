<?php

namespace Stickee\Instrumentation\Queue\Connectors;

use Illuminate\Queue\Connectors\SyncConnector as IlluminateSyncConnector;
use Stickee\Instrumentation\Queue\SyncQueueWithAvailableSize;

class SyncConnector extends IlluminateSyncConnector
{
    /** @inheritDoc */
    public function connect(array $config)
    {
        return new SyncQueueWithAvailableSize($config['after_commit'] ?? null);
    }
}
