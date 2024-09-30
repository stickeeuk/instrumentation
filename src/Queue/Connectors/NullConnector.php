<?php

namespace Stickee\Instrumentation\Queue\Connectors;

use Illuminate\Queue\Connectors\NullConnector as IlluminateNullConnector;
use Stickee\Instrumentation\Queue\NullQueueWithAvailableSize;

class NullConnector extends IlluminateNullConnector
{
    /** @inheritDoc */
    public function connect(array $config)
    {
        return new NullQueueWithAvailableSize();
    }
}
