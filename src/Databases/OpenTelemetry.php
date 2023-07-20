<?php
/**
 * OpenTelemetry instrumentation class file.
 *
 * This is used to gather metrics and send them to a metrics server.
 */

namespace Stickee\Instrumentation\Databases;

use Exception;
use Stickee\Instrumentation\Utils\OpenTelemetryConfig;

use OpenTelemetry\API\Metrics\MeterInterface;
use OpenTelemetry\API\Logs\EventLoggerInterface;
use OpenTelemetry\API\Logs\LogRecord;
use Stickee\Instrumentation\Databases\Traits\HandlesErrors;

/**
 * This class records metrics to OpenTelemetry
 */
class OpenTelemetry implements DatabaseInterface
{
    use HandlesErrors;

    /**
     * Events generated and waiting to be recorded
     *
     * @var array $events
     */
    private $events = [];

    /**
     * The config
     *
     * @var \Stickee\Instrumentation\Utils\OpenTelemetryConfig $config
     */
    private $config;

    /**
     * Counter instruments
     *
     * @var array $counters
     */
    private $counters = [];

    /**
     * Constructor
     */
    public function __construct(OpenTelemetryConfig $config)
    {
        $this->config = $config;
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
        $log = (new LogRecord($value))
            ->setTimestamp(time() * LogRecord::NANOS_PER_SECOND) // Can we do this at the processor level?
            ->setAttributes($tags);

        $this->config->eventLogger->logEvent($name, $log);
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
            $this->counters[$name] = $this->config->meter->createCounter($name);
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
        $this->config->meterProvider->forceFlush();
        $this->config->loggerProvider->forceFlush();
    }
}
