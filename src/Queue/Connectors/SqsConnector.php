<?php

namespace Stickee\Instrumentation\Queue\Connectors;

use Aws\Sqs\SqsClient;
use Illuminate\Queue\Connectors\SqsConnector as IlluminateSqsConnector;
use Illuminate\Support\Arr;
use Stickee\Instrumentation\Queue\SqsQueueWithAvailableSize;

class SqsConnector extends IlluminateSqsConnector
{
    /** @inheritDoc */
    public function connect(array $config)
    {
        $config = $this->getDefaultConfiguration($config);

        if (! empty($config['key']) && ! empty($config['secret'])) {
            $config['credentials'] = Arr::only($config, ['key', 'secret', 'token']);
        }

        return new SqsQueueWithAvailableSize(
            new SqsClient(
                Arr::except($config, ['token'])
            ),
            $config['queue'],
            $config['prefix'] ?? '',
            $config['suffix'] ?? '',
            $config['after_commit'] ?? null
        );
    }
}
