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
use InfluxDB\Database\RetentionPolicy;
use InfluxDB\Point;
use Stickee\Instrumentation\Exceptions\DatabaseWriteException;

/**
 * This class records metrics to InfluxDB
 */
class InfluxDb implements DatabaseInterface
{
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
    private $database = null;

    /**
     * The connection string, e.g. https+influxdb://username:password@localhost:8086/databasename
     *
     * @var string $dsn
     */
    private $dsn = '';

    /**
     * An error handler function that takes an Exception as an argument
     * Must be callable with `call_user_func()`
     *
     * @var mixed $errorHandler
     */
    private $errorHandler;

    /**
     * Create a connection to InfluxDB
     *
     * @param string $dsn The connection string
     */
    public function __construct(string $dsn)
    {
        $this->dsn = $dsn;
        $this->errorHandler = [$this, 'throwException'];
    }

    /**
     * Set the error handler
     *
     * @param mixed $errorHandler An error handler function that takes an Exception as an argument - must be callable with `call_user_func()`
     */
    public function setErrorHandler($errorHandler)
    {
        $this->errorHandler = $errorHandler;
    }

    /**
     * Default error handler - throw an exception
     *
     * @throws \Stickee\Instrumentation\Exceptions\DatabaseWriteException
     */
    private function throwException(Exception $e)
    {
        throw new DatabaseWriteException($e->getMessage(), $e->getCode(), $e);
    }

    /**
     * Class destructor
     */
    public function __destruct()
    {
        if (!$this->events) {
            return;
        }

        try {
            $database = $this->getDatabase();

            // Write the events to the database
            $database->writePoints($this->events, Database::PRECISION_MICROSECONDS);
        } catch (Exception $e) {
            call_user_func($this->errorHandler, $e);
        }
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
        $this->database = Client::fromDSN($this->dsn, 2, true, 2);

        return $this->database;
    }

    /**
     * Record an event
     *
     * @param string $event The class of event, e.g. "page_load"
     * @param array $tags An array of tags to attach to the event, e.g. ["code" => 200]
     */
    public function event(string $event, array $tags = []): void
    {
        $this->gauge($event, $tags, 1);
    }

    /**
     * Record an increase in a counter
     *
     * Use the `CUMULATIVE_SUM()` function in the InfluxDB query
     *
     * @param string $event The class of event, e.g. "page_load"
     * @param array $tags An array of tags to attach to the event, e.g. ["code" => 200]
     * @param float $increase The amount by which to increase the counter
     */
    public function count(string $event, array $tags = [], float $increase = 1): void
    {
        $this->gauge($event, $tags, $increase);
    }

    /**
     * Record the current value of a gauge
     *
     * @param string $event The name of the gauge, e.g. "queue_length"
     * @param array $tags An array of tags to attach to the event, e.g. ["datacentre" => "uk"]
     * @param float $value The value of the gauge
     */
    public function gauge(string $event, array $tags, float $value): void
    {
        $this->events[] = new Point($event, $value, $tags, [], (int)(microtime(true) * 1000000));
    }
}
