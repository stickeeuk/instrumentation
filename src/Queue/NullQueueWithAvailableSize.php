<?php

namespace Stickee\Instrumentation\Queue;

use Illuminate\Queue\NullQueue;

class NullQueueWithAvailableSize extends NullQueue
{
    /**
     * Get the number of available jobs in the queue
     *
     * @param string|null $queue The queue name
     */
    public function availableSize($queue = null): int
    {
        return 0;
    }
}