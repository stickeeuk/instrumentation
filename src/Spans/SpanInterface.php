<?php

namespace Stickee\Instrumentation\Spans;

use Throwable;

interface SpanInterface
{
    public function recordException(Throwable $exception): void;

    public function end(): void;
}
