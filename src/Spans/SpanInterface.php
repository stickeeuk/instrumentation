<?php

namespace Stickee\Instrumentation\Spans;

use Throwable;

interface SpanInterface
{
    /**
     * Record an exception
     *
     * @param \Throwable $exception The exception
     */
    public function recordException(Throwable $exception): void;

    /**
     * End the span
     */
    public function end(): void;
}
