<?php

namespace Stickee\Instrumentation\Queue;

use Illuminate\Queue\DatabaseQueue;
use Illuminate\Support\Carbon;

class DatabaseQueueWithAvailableSize extends DatabaseQueue
{
    /**
     * Get the number of available jobs in the queue
     *
     * @param string|null $queue The queue name
     */
    public function availableSize($queue = null): int
    {
        return $this->database->table($this->table)
            ->where('queue', $this->getQueue($queue))
            ->where('available_at', '<=', Carbon::now()->getTimestamp())
            ->count();
    }
}
