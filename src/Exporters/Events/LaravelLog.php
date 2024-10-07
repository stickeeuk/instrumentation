<?php

namespace Stickee\Instrumentation\Exporters\Events;

use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Psr\Log\LogLevel;
use Stickee\Instrumentation\Exporters\Events\Traits\WritesStrings;
use Stickee\Instrumentation\Exporters\Interfaces\EventsExporterInterface;
use Stickee\Instrumentation\Exporters\Traits\HandlesErrors;

/**
 * This class records metrics to the Laravel log
 */
class LaravelLog implements EventsExporterInterface
{
    use HandlesErrors;
    use WritesStrings;

    /**
     * Constructor
     *
     * @param string $level The log level - must be one from the RFC 5424 / PSR-3 specification (\Psr\Log\LogLevel)
     */
    public function __construct(private string $level = LogLevel::DEBUG)
    {
        if (! defined(LogLevel::class . '::' . mb_strtoupper($this->level))) {
            throw new InvalidArgumentException("Invalid log level: {$this->level}");
        }
    }

    /**
     * Write to the file
     *
     * @param string $message The message to write
     */
    protected function write(string $message): void
    {
        Log::{$this->level}($message);
    }
}
