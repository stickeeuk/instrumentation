<?php

namespace Stickee\Instrumentation\Databases;

use dump;
use Stickee\Instrumentation\Databases\Traits\HandlesErrors;
use Stickee\Instrumentation\Databases\Traits\WritesStrings;

/**
 * This class dumps metrics using `dump()`
 */
class LaravelDump implements DatabaseInterface
{
    use HandlesErrors, WritesStrings;

    /**
     * Write the message
     *
     * @param string $message The message to write
     */
    protected function write($message): void
    {
        dump($message);
    }
}
