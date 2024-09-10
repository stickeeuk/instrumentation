<?php

namespace Stickee\Instrumentation\Exporters\Events;

use Stickee\Instrumentation\Exporters\Events\Traits\WritesStrings;
use Stickee\Instrumentation\Exporters\Interfaces\EventsExporterInterface;
use Stickee\Instrumentation\Exporters\Traits\HandlesErrors;

/**
 * This class dumps metrics using `dump()`
 */
class LaravelDump implements EventsExporterInterface
{
    use HandlesErrors;
    use WritesStrings;

    /**
     * Write the message
     *
     * @param string $message The message to write
     */
    protected function write(string $message): void
    {
        dump($message);
    }
}
