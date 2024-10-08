<?php

namespace Stickee\Instrumentation\Spans;

use OpenTelemetry\API\Trace\SpanInterface as OpenTelemetrySpanInterface;
use OpenTelemetry\Context\ScopeInterface;
use Throwable;

class OpenTelemetrySpan implements SpanInterface
{
    /**
     * If the span has been ended
     */
    private bool $ended = false;

    /**
     * The OpenTelemetry scope
     */
    private readonly ScopeInterface $scope;

    /**
     * Constructor
     *
     * @param \OpenTelemetry\API\Trace\SpanInterface $span The OpenTelemetry span
     */
    public function __construct(/**
     * The OpenTelemetry span
     */
        private readonly OpenTelemetrySpanInterface $span
    ) {
        $this->scope = $this->span->activate();
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        if (! $this->ended) {
            $this->end();
        }
    }

    /**
     * Record an exception
     *
     * @param \Throwable $exception The exception
     */
    #[\Override]
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
    #[\Override]
    public function end(): void
    {
        $this->scope->detach();
        $this->span->end();

        $this->ended = true;
    }
}
