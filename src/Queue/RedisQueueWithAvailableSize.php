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

        return $this->getConnection()->eval(
            LuaScripts::size(),
            3,
            $queue,
            $queue . ':DUMMY',
            $queue . ':DUMMY'
        );
    }
}
