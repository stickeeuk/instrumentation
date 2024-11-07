<?php

namespace Stickee\Instrumentation\Queue;

use Illuminate\Queue\SqsQueue;

class SqsQueueWithAvailableSize extends SqsQueue
{
    /**
     * Get the number of available jobs in the queue
     *
     * @param string|null $queue The queue name
     */
    public function availableSize($queue = null): int
    {
        $response = $this->sqs->getQueueAttributes([
            'QueueUrl' => $this->getQueue($queue),
            'AttributeNames' => ['ApproximateNumberOfMessages', 'ApproximateNumberOfMessagesDelayed'],
        ]);

        $attributes = $response->get('Attributes');

        return (int) $attributes['ApproximateNumberOfMessages'] - (int) $attributes['ApproximateNumberOfMessagesDelayed'];
    }
}
