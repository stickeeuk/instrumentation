<?php
/**
 * InfluxDB instrumentation class file.
 *
 * This is used to gather metrics and send them to a metrics server.
 */

namespace Stickee\Instrumentation\Databases;

use InfluxDB\Database;
use InfluxDB\Client;
use InfluxDB\Point;
use InfluxDB\Database\RetentionPolicy;

/**
 * This class records metrics to InfluxDB.
 */
class InfluxDb implements DatabaseInterface
{
	/** @var array $events Events generated and waiting to be recorded. */
	private $events = [];

	/** @var string $database the connection to the InfluxDB database. */
	private $database = null;

	/** @var string $dsn The connection string, e.g. https+influxdb://username:password@localhost:8086/databasename */
	private $dsn = '';

	/**
	 * Create a connection to InfluxDB.
	 *
	 * @param string $dsn The connection string.
	 */
	public function __construct(string $dsn)
	{
		$this->dsn = $dsn;
	}

	/**
	 * Class destructor.
	 */
	public function __destruct()
	{
		if (!$this->events) {
			return;
		}

		$database = $this->getDatabase();

		// Write the events to the database.
		$database->writePoints($this->events, Database::PRECISION_MICROSECONDS);
	}

	/**
	 * Select the database.
	 * This will connect to the server if no connection is already established.
	 *
	 * @return Database Returns the InfluxDB database object.
	 */
	private function getDatabase(): Database
	{
		// If already connected, return the existing connection.
		if ($this->database) {
			return $this->database;
		}

		// Connect to InfluxDB.
		$this->database = Client::fromDSN($this->dsn, 2, true, 2);

		return $this->database;
	}

	/**
	 * Record an event.
	 *
	 * @param string $event The class of event, e.g. "page_load".
	 * @param array $tags An array of tags to attach to the event, e.g. ["code" => 200].
	 */
	public function event(string $event, array $tags = []): void
	{
		$this->gauge($event, $tags, 1);
	}

	/**
	 * Record an increase in a counter.
	 *
	 * Use the `CUMULATIVE_SUM()` function in the InfluxDB query.
	 *
	 * @param string $event The class of event, e.g. "page_load".
	 * @param array $tags An array of tags to attach to the event, e.g. ["code" => 200].
	 * @param float $increase The amount by which to increase the counter.
	 */
	public function count(string $event, array $tags = [], float $increase = 1): void
	{
		$this->gauge($event, $tags, $increase);
	}

	/**
	 * Record the current value of a gauge.
	 *
	 * @param string $event The name of the gauge, e.g. "queue_length".
	 * @param array $tags An array of tags to attach to the event, e.g. ["datacentre" => "uk"].
	 * @param float $value The value of the gauge.
	 */
	public function gauge(string $event, array $tags, float $value): void
	{
		$this->events[] = new Point($event, $value, $tags, [], (int)(microtime(true) * 1000000));
	}
}
