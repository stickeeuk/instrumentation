<?php

declare(strict_types=1);

namespace Stickee\Instrumentation\Tests\Fixtures;

use Stickee\Instrumentation\Exporters\Interfaces\EventsExporterInterface;

final class GoodEventsExporter implements EventsExporterInterface
{
    /**
     * Set the error handler
     *
     * @param mixed $errorHandler An error handler function that takes an Exception as an argument - must be callable with `call_user_func()`
     */
    public function setErrorHandler($errorHandler): void
    {
        // Do nothing
    }

    /**
     * Record an event
     *
     * @param string $name The name of the event, e.g. "page_load_time"
     * @param array $attributes An array of attributes to attach to the event, e.g. ["code" => 200]
     * @param float $value The value of the event, e.g. 12.3
     */
    public function event(string $name, array $attributes = [], float $value = 1): void
    {
        // Do nothing
    }

    /**
     * Record an increase in a counter
     *
     * @param string $name The counter name, e.g. "page_load"
     * @param array $attributes An array of attributes to attach to the event, e.g. ["code" => 200]
     * @param float $increase The amount by which to increase the counter
     */
    public function counter(string $name, array $attributes = [], float $increase = 1): void
    {
        // Do nothing
    }

    /**
     * Record the current value of a gauge
     *
     * @param string $name The name of the gauge, e.g. "queue_length"
     * @param array $attributes An array of attributes to attach to the event, e.g. ["datacentre" => "uk"]
     * @param float $value The value of the gauge
     */
    public function gauge(string $name, array $attributes, float $value): void
    {
        // Do nothing
    }

    /**
     * Record a value on a histogram
     *
     * @param string $name The name of the histogram, e.g. "http.server.duration"
     * @param float|int $value The value of the histogram
     * @param string|null $unit The unit of the histogram, e.g. "ms"
     * @param string|null $description A description of the histogram
     * @param array $buckets A set of buckets, e.g. [0.25, 0.5, 1, 5]
     * @param array $attributes An array of attributes to attach to the event, e.g. ["datacentre" => "uk"]
     */
    public function histogram(string $name, float|int $value, ?string $unit, ?string $description, array $buckets = [], array $attributes = []): void
    {
        // Do nothing
    }

    /**
     * Flush any queued writes
     */
    public function flush(): void
    {
        // Do nothing
    }
}
