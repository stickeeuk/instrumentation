<?php

namespace Stickee\Instrumentation\Queue\Connectors;

use Illuminate\Queue\Connectors\BeanstalkdConnector as IlluminateBeanstalkdConnector;
use Pheanstalk\Pheanstalk;
use Stickee\Instrumentation\Queue\BeanstalkdQueueWithAvailableSize;

class BeanstalkdConnector extends IlluminateBeanstalkdConnector
{
    /** @inheritDoc */
    public function connect(array $config)
    {
        return new BeanstalkdQueueWithAvailableSize(
            $this->pheanstalk($config),
            $config['queue'],
            $config['retry_after'] ?? Pheanstalk::DEFAULT_TTR,
            $config['block_for'] ?? 0,
            $config['after_commit'] ?? null
        );
    }
}
