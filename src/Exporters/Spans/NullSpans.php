<?php

namespace Stickee\Instrumentation\Exporters\Spans;

use OpenTelemetry\API\Trace\SpanKind;
use Stickee\Instrumentation\Exporters\Interfaces\SpansExporterInterface;
use Stickee\Instrumentation\Exporters\Traits\HandlesErrors;
use Stickee\Instrumentation\Spans\NullSpan;
use Stickee\Instrumentation\Spans\SpanInterface;

/**
 * Create Null spans
 */
class NullSpans implements SpansExporterInterface
{
    use HandlesErrors;

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
        return $callable(new NullSpan());
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
        return new NullSpan();
    }

    /**
     * Flush any queued writes
     */
    public function flush(): void
    {
        // Do nothing
    }
}
