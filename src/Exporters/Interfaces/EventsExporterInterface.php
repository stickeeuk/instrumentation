<?php

namespace Stickee\Instrumentation\Exporters\Interfaces;

/**
 * Instrumentation event exporter interface
 */
interface EventsExporterInterface extends HandlesErrorsInterface
{
    /**
     * Record an event
     *
     * @param string $name The name of the event, e.g. "page_load_time"
     * @param array $tags An array of tags to attach to the event, e.g. ["code" => 200]
     * @param float $value The value of the event, e.g. 12.3
     */
    public function event(string $name, array $tags = [], float $value = 1): void;

    /**
     * Record an increase in a counter
     *
     * @param string $name The counter name, e.g. "page_load"
     * @param array $tags An array of tags to attach to the event, e.g. ["code" => 200]
     * @param float $increase The amount by which to increase the counter
     */
    public function counter(string $name, array $tags = [], float $increase = 1): void;

    /**
     * Record the current value of a gauge
     *
     * @param string $name The name of the gauge, e.g. "queue_length"
     * @param array $tags An array of tags to attach to the event, e.g. ["datacentre" => "uk"]
     * @param float $value The value of the gauge
     */
    public function gauge(string $name, array $tags, float $value): void;

    /**
     * Record a value on a histogram
     *
     * @param string $name The name of the histogram, e.g. "http.server.duration"
     * @param string|null $unit The unit of the histogram, e.g. "ms"
     * @param string|null $description A description of the histogram
     * @param array $buckets An optional set of buckets, e.g. [0.25, 0.5, 1, 5]
     * @param float|int $value The value of the histogram
     * @param iterable<non-empty-string, string|bool|float|int|array|null> $attributes Attributes of the data point
     */
    public function histogram(string $name, ?string $unit, ?string $description, ?array $buckets = null, float|int $value, array $attributes = []): void;

    /**
     * Flush any queued writes
     */
    public function flush(): void;
}
