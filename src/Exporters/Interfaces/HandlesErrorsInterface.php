<?php

namespace Stickee\Instrumentation\Exporters\Interfaces;

/**
 * Handles errors interface
 */
interface HandlesErrorsInterface
{
    /**
     * Set the error handler
     *
     * @param mixed $errorHandler An error handler function that takes an Exception as an argument - must be callable with `call_user_func()`
     */
    public function setErrorHandler($errorHandler): void;
}
