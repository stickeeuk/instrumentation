<?php

namespace Stickee\Instrumentation\Exporters\Spans;

use OpenTelemetry\API\Trace\SpanKind;
use Stickee\Instrumentation\Exporters\Interfaces\SpansExporterInterface;
use Stickee\Instrumentation\Exporters\Traits\HandlesErrors;
use Stickee\Instrumentation\Spans\OpenTelemetrySpan;
use Stickee\Instrumentation\Spans\SpanInterface;
use Stickee\Instrumentation\Utils\CachedInstruments;
use Throwable;

/**
 * Create OpenTelemetry spans
 */
class OpenTelemetry implements SpansExporterInterface
{
    use HandlesErrors;

    public function __construct(private readonly CachedInstruments $instrumentation) {}

    /**
     * Creates a new span wrapping the given callable.
     * If an exception is thrown, the span is ended and the exception is recorded and rethrown.
     *
     * @param string $name The name of the span
     * @param callable $callable A callable that will be executed within the span context. The activated Span will be passed as the first argument.
     * @param SpanKind::KIND_* $kind The kind of span to create. Defaults to SpanKind::KIND_INTERNAL
     * @param iterable $attributes Attributes to add to the span. Defaults to an empty array, but can be any iterable.
     *
     * @return mixed The result of the callable
     */
    #[\Override]
    public function span(string $name, callable $callable, int $kind = SpanKind::KIND_INTERNAL, iterable $attributes = []): mixed
    {
        $span = $this->instrumentation
            ->tracer()
            ->spanBuilder($name)
            ->setSpanKind($kind)
            ->setAttributes($attributes)
            ->startSpan();
        $spanScope = $span->activate();

        try {
            return $callable($span);
        } catch (Throwable $throwable) {
            $span->recordException($throwable, [
                'exception.line' => $throwable->getLine(),
                'exception.file' => $throwable->getFile(),
                'exception.code' => $throwable->getCode(),
            ]);

            throw $throwable;
        } finally {
            $spanScope->detach();
            $span->end();
        }
    }

    /**
     * Start a span and scope
     *
     * @param string $name The name of the span
     * @param SpanKind::KIND_* $kind The kind of span to create. Defaults to SpanKind::KIND_INTERNAL
     * @param iterable $attributes Attributes to add to the span. Defaults to an empty array, but can be any iterable.
     */
    #[\Override]
    public function startSpan(string $name, int $kind = SpanKind::KIND_INTERNAL, iterable $attributes = []): SpanInterface
    {
        $span = $this->instrumentation
            ->tracer()
            ->spanBuilder($name)
            ->setSpanKind($kind)
            ->setAttributes($attributes)
            ->startSpan();

        return new OpenTelemetrySpan($span);
    }

    /**
     * Flush any queued writes
     */
    #[\Override]
    public function flush(): void
    {
        // Do nothing
    }
}
