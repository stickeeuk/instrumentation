<?php

namespace Stickee\Instrumentation\Queue;

use Illuminate\Queue\LuaScripts;
use Illuminate\Queue\RedisQueue;

class RedisQueueWithAvailableSize extends RedisQueue
{
    /**
     * Get the number of available jobs in the queue
     *
     * @param string|null $queue The queue name
     */
    public function availableSize($queue = null): int
    {
        $queue = $this->getQueue($queue);

        return $this->getConnection()->eval( // @phpstan-ignore arguments.count
            LuaScripts::size(),
            3, // @phpstan-ignore argument.type
            $queue, // @phpstan-ignore argument.type
            $queue . ':DUMMY',
            $queue . ':DUMMY'
        );
    }
}
