<?php

namespace Stickee\Instrumentation\Exporters\Traits;

use Throwable;
use Stickee\Instrumentation\Exceptions\WriteException;

/**
 * Handles errors with a callback
 */
trait HandlesErrors
{
    /**
     * An error handler function that takes an Exception as an argument
     * Must be callable with `call_user_func()`
     *
     * @var mixed
     */
    private $errorHandler;

    /**
     * Set the error handler
     *
     * @param mixed $errorHandler An error handler function that takes an Exception as an argument - must be callable with `call_user_func()`
     */
    public function setErrorHandler($errorHandler): void
    {
        $this->errorHandler = $errorHandler;
    }

    /**
     * Handle an exception
     *
     * @param \Throwable $e The exception to handle
     */
    protected function handleError(Throwable $e): void
    {
        if ($this->errorHandler) {
            call_user_func($this->errorHandler, $e);
        } else {
            throw new WriteException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
