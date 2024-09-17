<?php

namespace Stickee\Instrumentation\Exporters\Events;

use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Logs\LogRecord;
use OpenTelemetry\SDK\Logs\EventLogger;
use OpenTelemetry\SDK\Logs\EventLoggerProviderInterface;
use OpenTelemetry\SDK\Metrics\Meter;
use OpenTelemetry\SDK\Metrics\MeterProviderInterface;
use OpenTelemetry\SDK\Trace\Span;
use Stickee\Instrumentation\Exporters\Interfaces\EventsExporterInterface;
use Stickee\Instrumentation\Exporters\Traits\HandlesErrors;

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

    private readonly EventLogger $eventLogger;
    private readonly Meter $meter;

    public function __construct(
        private readonly EventLoggerProviderInterface $eventLoggerProvider,
        private readonly MeterProviderInterface $meterProvider,
    ) {
        $this->eventLogger = $eventLoggerProvider->getEventLogger('instrumentation');
        $this->meter = $meterProvider->getMeter('instrumentation');
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
        // TODO use this as well? Instead?
        // Span::getCurrent()->addEvent($name, $tags);

        $log = (new LogRecord($value))
            ->setTimestamp(microtime(true) * LogRecord::NANOS_PER_SECOND) // Can we do this at the processor level?
            ->setAttributes($tags);

        $this->eventLogger->emit($name, $log);
    }

    /**
     * Record an increase in a counter
     *
     * @param string $name The counter name, e.g. "page_load"
     * @param array $tags An array of tags to attach to the event, e.g. ["code" => 200]
     * @param float $increase The amount by which to increase the counter
     */
    public function count(string $name, array $tags = [], float $increase = 1): void
    {
        if (!isset($this->counters[$name])) {
            $this->counters[$name] = $this->meter->createCounter($name);
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
     * Flush any queued writes
     */
    public function flush(): void
    {
        $this->meterProvider->forceFlush();
        $this->eventLoggerProvider->forceFlush();
    }
}
