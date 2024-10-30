<?php

namespace Stickee\Instrumentation\Exporters;

use OpenTelemetry\API\Trace\SpanKind;
use Stickee\Instrumentation\DataScrubbers\DataScrubberInterface;
use Stickee\Instrumentation\Exporters\Interfaces\EventsExporterInterface;
use Stickee\Instrumentation\Exporters\Interfaces\SpansExporterInterface;
use Stickee\Instrumentation\Spans\SpanInterface;

class Exporter implements EventsExporterInterface, SpansExporterInterface
{
    /**
     * Constructor
     *
     * @param \Stickee\Instrumentation\Exporters\Interfaces\EventsExporterInterface $eventsExporter The events exporter
     * @param \Stickee\Instrumentation\Exporters\Interfaces\SpansExporterInterface $spansExporter The spans exporter
     * @param \Stickee\Instrumentation\DataScrubbers\DataScrubberInterface $dataScrubber The data scrubber
     */
    public function __construct(
        private readonly EventsExporterInterface $eventsExporter,
        private readonly SpansExporterInterface $spansExporter,
        private readonly DataScrubberInterface $dataScrubber
    ) {}

    /**
     * Set the error handler
     *
     * @param mixed $errorHandler An error handler function that takes an Exception as an argument - must be callable with `call_user_func()`
     */
    #[\Override]
    public function setErrorHandler($errorHandler): void
    {
        $this->eventsExporter->setErrorHandler($errorHandler);
        $this->spansExporter->setErrorHandler($errorHandler);
    }

    /**
     * Record an event
     *
     * @param string $name The name of the event, e.g. "page_load_time"
     * @param array $attributes An array of attributes to attach to the event, e.g. ["code" => 200]
     * @param float $value The value of the event, e.g. 12.3
     */
    #[\Override]
    public function event(string $name, array $attributes = [], float $value = 1): void
    {
        $attributes = $this->scrub($attributes);

        $this->eventsExporter->event($name, $attributes, $value);
    }

    /**
     * Record an increase in a counter
     *
     * @param string $name The counter name, e.g. "page_load"
     * @param array $attributes An array of attributes to attach to the event, e.g. ["code" => 200]
     * @param float $increase The amount by which to increase the counter
     */
    #[\Override]
    public function counter(string $name, array $attributes = [], float $increase = 1): void
    {
        $attributes = $this->scrub($attributes);

        $this->eventsExporter->counter($name, $attributes, $increase);
    }

    /**
     * Record the current value of a gauge
     *
     * @param string $name The name of the gauge, e.g. "queue_length"
     * @param array $attributes An array of attributes to attach to the event, e.g. ["datacentre" => "uk"]
     * @param float $value The value of the gauge
     */
    #[\Override]
    public function gauge(string $name, array $attributes, float $value): void
    {
        $attributes = $this->scrub($attributes);

        $this->eventsExporter->gauge($name, $attributes, $value);
    }

    /**
     * Record a value on a histogram
     *
     * @param string $name The name of the histogram, e.g. "http.server.duration"
     * @param string|null $unit The unit of the histogram, e.g. "ms"
     * @param string|null $description A description of the histogram
     * @param array $buckets A set of buckets, e.g. [0.25, 0.5, 1, 5]
     * @param array $attributes An array of attributes to attach to the event, e.g. ["datacentre" => "uk"]
     * @param float|int $value The value of the histogram
     */
    #[\Override]
    public function histogram(string $name, ?string $unit, ?string $description, array $buckets, array $attributes, float|int $value): void
    {
        $attributes = $this->scrub($attributes);

        $this->eventsExporter->histogram($name, $unit, $description, $buckets, $attributes, $value);
    }

    /**
     * Flush any queued writes
     */
    #[\Override]
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
    #[\Override]
    public function span(string $name, callable $callable, int $kind = SpanKind::KIND_INTERNAL, iterable $attributes = []): mixed
    {
        $attributes = $this->scrub($attributes);

        return $this->spansExporter->span($name, $callable, $kind, $attributes);
    }

    /**
     * Start a span and scope
     *
     * @param string $name The name of the span
     * @param int $kind The kind of span to create. Defaults to SpanKind::KIND_INTERNAL
     * @param iterable $attributes Attributes to add to the span. Defaults to an empty array, but can be any iterable.
     */
    #[\Override]
    public function startSpan(string $name, int $kind = SpanKind::KIND_INTERNAL, iterable $attributes = []): SpanInterface
    {
        $attributes = $this->scrub($attributes);

        return $this->spansExporter->startSpan($name, $kind, $attributes);
    }

    /**
     * Scrub data
     *
     * @param array $data The data to scrub
     */
    private function scrub(array $data): array
    {
        foreach ($data as $key => $value) {
            $data[$key] = $this->dataScrubber->scrub($key, $value);
        }

        return $data;
    }
}
