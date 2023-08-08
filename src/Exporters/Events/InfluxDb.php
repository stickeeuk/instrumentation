<?php

namespace Stickee\Instrumentation\Exporters\Events;

use Exception;
use InfluxDB2\Client;
use InfluxDB2\Model\WritePrecision;
use InfluxDB2\Point;
use InfluxDB2\WriteType;
use Stickee\Instrumentation\Exporters\Interfaces\EventsExporterInterface;
use Stickee\Instrumentation\Exporters\Traits\HandlesErrors;

/**
 * This class records metrics to InfluxDB
 */
class InfluxDb implements EventsExporterInterface
{
    use HandlesErrors;

    /**
     * Events generated and waiting to be recorded
     *
     * @var \InfluxDB2\Point[] $events
     */
    private $events = [];

    /**
     * The connection to the InfluxDB database
     *
     * @var \InfluxDB2\Client $client
     */
    private $client;

    /**
     * Constructor
     *
     * @param string $url The connection URL
     * @param string $token The connection token
     * @param string $bucket The bucket name
     * @param string $org The organisation name
     * @param bool $verifySsl Verify the SSL certificate
     */
    public function __construct(string $url, string $token, string $bucket, string $org, bool $verifySsl = true)
    {
        $this->client = new Client([
            'url' => $url,
            'token' => $token,
            'bucket' => $bucket,
            'org' => $org,
            'precision' => WritePrecision::NS,
            'timeout' => 2,
            'verifySSL' => $verifySsl,
        ]);
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
        $this->gauge($name, $tags, $value);
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
        $this->gauge($name, $tags, $increase);
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
        // TODO tag span and trace
        $this->events[] = new Point($name, $tags, ['value' => $value]);
    }

    /**
     * Flush any queued writes
     */
    public function flush(): void
    {
        if (!$this->events) {
            return;
        }

        try {
            $writeApi = $this->client->createWriteApi(["writeType" => WriteType::BATCHING, 'batchSize' => 1000]);

            foreach ($this->events as $event) {
                $writeApi->write($event);
            }

            $writeApi->close();

            $this->events = [];
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }
}
