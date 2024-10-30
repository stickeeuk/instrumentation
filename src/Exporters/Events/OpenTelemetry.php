<?php

namespace Stickee\Instrumentation\Exporters\Events;

use OpenTelemetry\API\Logs\LogRecord;
use OpenTelemetry\SDK\Trace\Span;
use Stickee\Instrumentation\Exporters\Interfaces\EventsExporterInterface;
use Stickee\Instrumentation\Exporters\Traits\HandlesErrors;
use Stickee\Instrumentation\Utils\CachedInstruments;

/**
 * This class records metrics to OpenTelemetry
 */
class OpenTelemetry implements EventsExporterInterface
{
    use HandlesErrors;

    /**
     * Counter instruments
     */
    private array $counters = [];

    /**
     * Gauge instruments
     */
    private array $gauges = [];

    /**
     * Histogram instruments
     */
    private array $histograms = [];

    /**
     * Constructor
     *
     * @param \Stickee\Instrumentation\Utils\CachedInstruments $instrumentation The instrumentation
     */
    public function __construct(private readonly CachedInstruments $instrumentation) {}

    /**
     * Class destructor
     */
    public function __destruct()
    {
        $this->flush();
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
        Span::getCurrent()->addEvent($name, $attributes);

        $log = (new LogRecord($value))
            ->setTimestamp((int) microtime(true) * LogRecord::NANOS_PER_SECOND)
            ->setAttributes($attributes);

        $this->instrumentation->eventLogger()->emit($name, $log);
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
        if (! isset($this->counters[$name])) {
            $this->counters[$name] = $this->instrumentation->meter()->createCounter($name);
        }

        $this->counters[$name]->add($increase, $attributes);
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
        if (! isset($this->gauges[$name])) {
            $this->gauges[$name] = $this->instrumentation->meter()->createGauge($name);
        }

        $this->gauges[$name]->record($value, $attributes);
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
    public function histogram(string $name, ?string $unit, ?string $description, array $buckets = [], array $attributes = [], float|int $value): void
    {
        if (! isset($this->histograms[$name])) {
            $advisory = [];

            if ($buckets !== []) {
                $advisory['ExplicitBucketBoundaries'] = $buckets;
            }

            $this->histograms[$name] = $this->instrumentation->meter()->createHistogram($name, $unit, $description, $advisory);
        }

        $this->histograms[$name]->record($value, $attributes);
    }

    /**
     * Flush any queued writes
     */
    #[\Override]
    public function flush(): void
    {
        $this->instrumentation->flush();
    }
}
