<?php

namespace Stickee\Instrumentation\Databases;

use Log;
use Stickee\Instrumentation\Databases\Traits\HandlesErrors;
use Stickee\Instrumentation\Databases\Traits\NullSpans;
use Stickee\Instrumentation\Databases\Traits\WritesStrings;

/**
 * This class records metrics to the Laravel log
 */
class LaravelLog implements DatabaseInterface
{
    use HandlesErrors;
    use WritesStrings;
    use NullSpans;

    /**
     * The log level
     *
     * @var string $level
     */
    private $level;

    /**
     * An error handler function that takes an Exception as an argument
     * Must be callable with `call_user_func()`
     *
     * @var mixed $errorHandler
     */
    private $errorHandler;

    /**
     * Constructor
     *
     * @param string $level The log level - must be one from the RFC 5424 specification: emergency, alert, critical, error, warning, notice, info and debug
     */
    public function __construct(string $level = 'debug')
    {
        $this->level = $level;
    }

    /**
     * Write to the file
     *
     * @param string $message The message to write
     */
    protected function write($message): void
    {
        Log::{$this->level}($message);
    }
}
