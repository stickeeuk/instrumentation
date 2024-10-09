<?php

namespace Stickee\Instrumentation\Spans;

use Throwable;

class NullSpan implements SpanInterface
{
    /**
     * Record an exception
     *
     * @param \Throwable $exception The exception
     */
    #[\Override]
    public function recordException(Throwable $exception): void
    {
        // Do nothing
    }

    /**
     * End the span
     */
    #[\Override]
    public function end(): void
    {
        // Do nothing
    }
}
