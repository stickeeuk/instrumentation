<?php

namespace Stickee\Instrumentation\Queue;

use Illuminate\Queue\BeanstalkdQueue;

class BeanstalkdQueueWithAvailableSize extends BeanstalkdQueue
{
    /**
     * Get the number of available jobs in the queue
     *
     * @param string|null $queue The queue name
     */
    public function availableSize($queue = null): int
    {
        // Size is already the availableSize, unlike other drivers
        return $this->size($queue);
    }
}
