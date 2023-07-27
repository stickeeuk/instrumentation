<?php

namespace Stickee\Instrumentation\Spans;

use OpenTelemetry\API\Trace\SpanInterface as OpenTelemetrySpanInterface;
use OpenTelemetry\Context\ScopeInterface;
use Throwable;

class OpenTelemetrySpan implements SpanInterface
{
    private bool $ended = false;
    private OpenTelemetrySpanInterface $span;
    private ScopeInterface $scope;

    public function __construct(OpenTelemetrySpanInterface $span)
    {
        $this->span = $span;
        $this->scope = $span->activate();
    }

    public function recordException(Throwable $exception): void
    {
        $this->span->recordException($exception, [
            'exception.line' => $exception->getLine(),
            'exception.file' => $exception->getFile(),
            'exception.code' => $exception->getCode(),
        ]);
    }

    public function end(): void
    {
        $this->scope->detach();
        $this->span->end();

        $this->ended = true;
    }

    public function __destruct()
    {
        if (!$this->ended) {
            $this->end();
        }
    }
}
