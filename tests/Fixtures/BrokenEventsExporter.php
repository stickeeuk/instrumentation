<?php

declare(strict_types=1);

namespace Stickee\Instrumentation\Tests\Fixtures;

use Exception;
use Stickee\Instrumentation\Exporters\Interfaces\EventsExporterInterface;
use Stickee\Instrumentation\Exporters\Traits\HandlesErrors;

final class BrokenEventsExporter implements EventsExporterInterface
{
    use HandlesErrors;

    /**
     * Record an event
     *
     * @param string $name The name of the event, e.g. "page_load_time"
     * @param array $attributes An array of attributes to attach to the event, e.g. ["code" => 200]
     * @param float $value The value of the event, e.g. 12.3
     */
    public function event(string $name, array $attributes = [], float $value = 1): void
    {
        $this->handleError(new Exception('This is a test exception'));
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
        $this->handleError(new Exception('This is a test exception'));
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
        $this->handleError(new Exception('This is a test exception'));
    }

    /**
     * Record a value on a histogram
     *
     * @param string $name The name of the histogram, e.g. "http.server.duration"
     * @param string|null $unit The unit of the histogram, e.g. "ms"
     * @param string|null $description A description of the histogram
     * @param array $buckets An optional set of buckets, e.g. [0.25, 0.5, 1, 5]
     * @param float|int $value The non-negative value of the histogram
     * @param array $attributes An array of attributes to attach to the event, e.g. ["datacentre" => "uk"]
     */
    public function histogram(string $name, ?string $unit, ?string $description, ?array $buckets, float|int $value, array $attributes = []): void
    {
        $this->handleError(new Exception('This is a test exception'));
    }

    /**
     * Flush any queued writes
     */
    public function flush(): void
    {
        // Do nothing
    }
}
