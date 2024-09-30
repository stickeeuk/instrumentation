<?php

namespace Stickee\Instrumentation\Queue\Connectors;

use Illuminate\Queue\Connectors\RedisConnector as IlluminateRedisConnector;
use Stickee\Instrumentation\Queue\RedisQueueWithAvailableSize;

class RedisConnector extends IlluminateRedisConnector
{
    /** @inheritDoc */
    public function connect(array $config)
    {
        return new RedisQueueWithAvailableSize(
            $this->redis, $config['queue'],
            $config['connection'] ?? $this->connection,
            $config['retry_after'] ?? 60,
            $config['block_for'] ?? null,
            $config['after_commit'] ?? null,
            $config['migration_batch_size'] ?? -1
        );
    }
}
