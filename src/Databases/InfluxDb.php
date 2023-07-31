<?php
/**
 * InfluxDB instrumentation class file.
 *
 * This is used to gather metrics and send them to a metrics server.
 */

namespace Stickee\Instrumentation\Databases;

use Exception;
use InfluxDB\Client;
use InfluxDB\Database;
use InfluxDB\Point;
use Stickee\Instrumentation\Databases\Traits\HandlesErrors;
use Stickee\Instrumentation\Databases\Traits\NullSpans;

/**
 * This class records metrics to InfluxDB
 */
class InfluxDb implements DatabaseInterface
{
    use HandlesErrors;
    use NullSpans;

    /**
     * Events generated and waiting to be recorded
     *
     * @var array $events
     */
    private $events = [];

    /**
     * The connection to the InfluxDB database
     *
     * @var string $database
     */
    private $database;

    /**
     * The connection string, e.g. https+influxdb://username:password@localhost:8086/databasename
     *
     * @var string $dsn
     */
    private $dsn;

    /**
     * Verify the database SSL certificate
     *
     * @var bool $verifySsl
     */
    private $verifySsl;

    /**
     * Constructor
     *
     * @param string $dsn The connection string
     * @param bool $verifySsl Verify the database SSL certificate
     */
    public function __construct(string $dsn, bool $verifySsl = true)
    {
        if (!class_exists(Client::class)) {
            throw new Exception('InfluxDB client library not installed, please run: composer require influxdb/influxdb-php');
        }

        $this->dsn = $dsn;
        $this->verifySsl = $verifySsl;
    }

    /**
     * Class destructor
     */
    public function __destruct()
    {
        $this->flush();
    }

    /**
     * Get the database connection
     * This will connect to the server if no connection is already established
     *
     * @return \InfluxDB\Database Returns the InfluxDB database object
     */
    private function getDatabase(): Database
    {
        // If already connected, return the existing connection
        if ($this->database) {
            return $this->database;
        }

        // Connect to InfluxDB
        $this->database = Client::fromDSN($this->dsn, 2, $this->verifySsl, 2);

        return $this->database;
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
        $this->events[] = new Point($name, $value, $tags, [], (int)(microtime(true) * 1000000));
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
            $database = $this->getDatabase();

            // Write the events to the database
            $database->writePoints($this->events, Database::PRECISION_MICROSECONDS);

            $this->events = [];
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }
}
