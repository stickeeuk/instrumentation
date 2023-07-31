<?php

namespace Stickee\Instrumentation\Spans;

use Throwable;

class NullSpan implements SpanInterface
{
    public function recordException(Throwable $exception): void
    {
        // Do nothing
    }

    public function end(): void
    {
        // Do nothing
    }
}
