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
     *
     * @var array $counters
     */
    private $counters = [];
    private $histograms = [];

    public function __construct(private readonly CachedInstruments $instrumentation)
    {
    }

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
     * @param array $tags An array of tags to attach to the event, e.g. ["code" => 200]
     * @param float $value The value of the event, e.g. 12.3
     */
    public function event(string $name, array $tags = [], float $value = 1): void
    {
        Span::getCurrent()->addEvent($name, $tags);

        $log = (new LogRecord($value))
            ->setTimestamp(microtime(true) * LogRecord::NANOS_PER_SECOND)
            ->setAttributes($tags);

        $this->instrumentation->eventLogger()->emit($name, $log);
    }

    /**
     * Record an increase in a counter
     *
     * @param string $name The counter name, e.g. "page_load"
     * @param array $tags An array of tags to attach to the event, e.g. ["code" => 200]
     * @param float $increase The amount by which to increase the counter
     */
    public function counter(string $name, array $tags = [], float $increase = 1): void
    {
        if (!isset($this->counters[$name])) {
            $this->counters[$name] = $this->instrumentation->meter()->createCounter($name);
        }

        $this->counters[$name]->add($increase, $tags);
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
        $this->event($name, $tags, $value);
    }

    /**
     * Record a value on a histogram
     *
     * @param string $name The name of the histogram, e.g. "http.server.duration"
     * @param string|null $unit The unit of the histogram, e.g. "ms"
     * @param string|null $description A description of the histogram
     * @param array $buckets An optional set of buckets, e.g. [0.25, 0.5, 1, 5]
     * @param float|int $value The non-negative value of the histogram
     * @param array $tags An array of tags to attach to the event, e.g. ["datacentre" => "uk"]
     */
    public function histogram(string $name, ?string $unit, ?string $description, ?array $buckets = null, float|int $value, array $tags = []): void
    {
        if (!isset($this->histograms[$name])) {
            $advisory = [];

            if ($buckets !== null) {
                $advisory['ExplicitBucketBoundaries'] = $buckets;
            }

            $this->histograms[$name] = $this->instrumentation->meter()->createHistogram($name, $unit, $description, $advisory);
        }

        $this->histograms[$name]->record($value, $tags);
    }

    /**
     * Flush any queued writes
     */
    public function flush(): void
    {
        $this->instrumentation->flush();
    }
}
