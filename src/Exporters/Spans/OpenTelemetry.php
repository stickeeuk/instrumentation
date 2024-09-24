<?php

namespace Stickee\Instrumentation\Exporters\Spans;

use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\SDK\Trace\Tracer;
use OpenTelemetry\SDK\Trace\TracerProviderInterface;
use Stickee\Instrumentation\Exporters\Interfaces\SpansExporterInterface;
use Stickee\Instrumentation\Exporters\Traits\HandlesErrors;
use Stickee\Instrumentation\Spans\OpenTelemetrySpan;
use Stickee\Instrumentation\Spans\SpanInterface;
use Throwable;

/**
 * Create OpenTelemetry spans
 */
class OpenTelemetry implements SpansExporterInterface
{
    use HandlesErrors;

    private Tracer $tracer;

    public function __construct(TracerProviderInterface $tracerProvider)
    {
        $this->tracer = $tracerProvider->getTracer('instrumentation');
    }

    /**
     * Creates a new span wrapping the given callable.
     * If an exception is thrown, the span is ended and the exception is recorded and rethrown.
     *
     * @param string $name The name of the span
     * @param callable $callable A callable that will be executed within the span context. The activated Span will be passed as the first argument.
     * @param int $kind The kind of span to create. Defaults to SpanKind::KIND_INTERNAL
     * @param iterable $attributes Attributes to add to the span. Defaults to an empty array, but can be any iterable.
     *
     * @return mixed The result of the callable
     */
    public function span(string $name, callable $callable, int $kind = SpanKind::KIND_INTERNAL, iterable $attributes = []): mixed
    {
        $span = $this->tracer->spanBuilder($name)
            ->setSpanKind($kind)
            ->setAttributes($attributes)
            ->startSpan();
        $spanScope = $span->activate();

        try {
            return $callable($span);
        } catch (Throwable $e) {
            $span->recordException($e, [
                'exception.line' => $e->getLine(),
                'exception.file' => $e->getFile(),
                'exception.code' => $e->getCode(),
            ]);
            throw $e;
        } finally {
            $spanScope->detach();
            $span->end();
        }
    }

    /**
     * Start a span and scope
     *
     * @param string $name The name of the span
     * @param int $kind The kind of span to create. Defaults to SpanKind::KIND_INTERNAL
     * @param iterable $attributes Attributes to add to the span. Defaults to an empty array, but can be any iterable.
     *
     * @return \Stickee\Instrumentation\Spans\SpanInterface
     */
    public function startSpan(string $name, int $kind = SpanKind::KIND_INTERNAL, iterable $attributes = []): SpanInterface
    {
        $span = $this->tracer->spanBuilder($name)
            ->setSpanKind($kind)
            ->setAttributes($attributes)
            ->startSpan();

        return new OpenTelemetrySpan($span);
    }

    /**
     * Flush any queued writes
     */
    public function flush(): void
    {
        // Do nothing
    }
}
