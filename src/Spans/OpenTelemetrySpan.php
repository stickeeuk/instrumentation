<?php

namespace Stickee\Instrumentation\Spans;

use OpenTelemetry\API\Trace\SpanInterface as OpenTelemetrySpanInterface;
use OpenTelemetry\Context\ScopeInterface;
use Throwable;

class OpenTelemetrySpan implements SpanInterface
{
    /**
     * If the span has been ended
     *
     * @var bool $ended
     */
    private bool $ended = false;

    /**
     * The OpenTelemetry span
     *
     * @var \OpenTelemetry\API\Trace\SpanInterface $span
     */
    private OpenTelemetrySpanInterface $span;

    /**
     * The OpenTelemetry scope
     *
     * @var \OpenTelemetry\Context\ScopeInterface $scope
     */
    private ScopeInterface $scope;

    /**
     * Constructor
     *
     * @param \OpenTelemetry\API\Trace\SpanInterface $span The OpenTelemetry span
     */
    public function __construct(OpenTelemetrySpanInterface $span)
    {
        $this->span = $span;
        $this->scope = $span->activate();
    }

    /**
     * Record an exception
     *
     * @param \Throwable $exception The exception
     */
    public function recordException(Throwable $exception): void
    {
        $this->span->recordException($exception, [
            'exception.line' => $exception->getLine(),
            'exception.file' => $exception->getFile(),
            'exception.code' => $exception->getCode(),
        ]);
    }

    /**
     * End the span
     */
    public function end(): void
    {
        $this->scope->detach();
        $this->span->end();

        $this->ended = true;
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        if (!$this->ended) {
            $this->end();
        }
    }
}
