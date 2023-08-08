<?php

namespace Stickee\Instrumentation\Exporters;

use OpenTelemetry\API\Trace\SpanKind;
use Stickee\Instrumentation\Exporters\Interfaces\EventsExporterInterface;
use Stickee\Instrumentation\Exporters\Interfaces\SpansExporterInterface;
use Stickee\Instrumentation\Spans\SpanInterface;

class Exporter implements EventsExporterInterface, SpansExporterInterface
{
    private EventsExporterInterface $eventsExporter;
    private SpansExporterInterface $spansExporter;

    /**
     * Constructor
     *
     * @param string $dsn The connection string
     * @param bool $verifySsl Verify the database SSL certificate
     */
    public function __construct(EventsExporterInterface $eventsExporter, SpansExporterInterface $spansExporter)
    {
        $this->eventsExporter = $eventsExporter;
        $this->spansExporter = $spansExporter;
    }

    /**
     * Set the error handler
     *
     * @param mixed $errorHandler An error handler function that takes an Exception as an argument - must be callable with `call_user_func()`
     */
    public function setErrorHandler($errorHandler): void
    {
        $this->eventsExporter->setErrorHandler($errorHandler);
        $this->spansExporter->setErrorHandler($errorHandler);
    }

    /**
     * Record an event
     *
     * @param string $name The name of the event, e.g. "page_load_time"
     * @param array $tags An array of tags to attach to the event, e.g. ["code" => 200]
     * @param float $value The value of the event, e.g. 12.3
     */
    public function event(string $name, array $tags = [], float $value = 1): void
    {
        $this->eventsExporter->event($name, $tags, $value);
    }

    /**
     * Record an increase in a counter
     *
     * Use the `CUMULATIVE_SUM()` function in the InfluxDB query
     *
     * @param string $name The counter name, e.g. "page_load"
     * @param array $tags An array of tags to attach to the event, e.g. ["code" => 200]
     * @param float $increase The amount by which to increase the counter
     */
    public function count(string $name, array $tags = [], float $increase = 1): void
    {
        $this->eventsExporter->count($name, $tags, $increase);
    }

    /**
     * Record the current value of a gauge
     *
     * @param string $name The name of the gauge, e.g. "queue_length"
     * @param array $tags An array of tags to attach to the event, e.g. ["datacentre" => "uk"]
     * @param float $value The value of the gauge
     */
    public function gauge(string $name, array $tags, float $value): void
    {
        $this->eventsExporter->gauge($name, $tags, $value);
    }

    /**
     * Flush any queued writes
     */
    public function flush(): void
    {
        $this->eventsExporter->flush();
        $this->spansExporter->flush();
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
        return $this->spansExporter->span($name, $callable, $kind, $attributes);
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
        return $this->spansExporter->startSpan($name, $kind, $attributes);
    }
}
