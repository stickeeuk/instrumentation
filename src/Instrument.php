<?php
/**
 * Instrument class file.
 *
 * This is used to gather metrics and send them to a metrics server.
 *
 * Usage:
 *
 * ```php
 *
 * // Prepare the instrumentation database connection.
 * $influxDb = new InfluxDb($host, $port, $database);
 *
 * // Add it to Instrument.
 * Instrument::add($influxDb); // Set as default
 *
 * // Record an event.
 * Instrument::event('load');
 *
 * // Add a secondary database.
 * $otherDb = new InfluxDb($host, $port, $database);
 * Instrument::add($otherDb, 'other');
 *
 * // Add an event to the secondary database.
 * Instrument::get('other')->event('test');
 *
 * // To disable a database (e.g. for dev), add a NullDatabase instead
 * if (config('instrumentation.enabled')) {
 *     $db = new InfluxDb($host, $port, $database);
 * } else {
 *     $db = new NullDatabase();
 * }
 *
 * Instrument::add($db);
 *
 * ```
 *
 * Metric Types:
 *
 * Event: A single action that has happened.
 * Counter: A value that can only increase.
 * Gauge: A value that may go up and down.
 */

namespace Stickee\Instrumentation;

use Stickee\Instrumentation\Databases\DatabaseInterface;
use InvalidArgumentException;

/**
 * The Instrument class records metrics.
 */
class Instrument implements DatabaseInterface, InstrumentInterface
{
	/** @var string DEFAULT_DATABASE The default database name */
	public const DEFAULT_DATABASE = 'default';

	/** @var array $databases Instrumentation databases that can be written to. */
	private $databases = [];

	/**
	 * Add a database to which metrics can be written.
	 *
	 * @param DatabaseInterface $database The database to add.
	 * @param string $name The name of the database, for use with `get()` or null to use the default.
	 *
	 * @throws InvalidArgumentException Throws if `$name` has already been added.
	 */
	public function add(DatabaseInterface $database, string $name = Instrument::DEFAULT_DATABASE): void
	{
		if (isset($this->databases[$name])) {
			throw new InvalidArgumentException('The database name "' . $name . '" has already been added.');
		}

		$this->databases[$name] = $database;
	}

	/**
	 * Get a database by name.
	 *
	 * @param string $name The name of the database to get.  Empty string for default.
	 */
	public function get(string $name = Instrument::DEFAULT_DATABASE): DatabaseInterface
	{
		if (empty($this->databases[$name])) {
			throw new InvalidArgumentException('Unknown database name "' . $name . '"');
		}

		return $this->databases[$name];
	}

	/**
	 * Record an event.
	 * This is a shortcut for Instrument::get(Instrument::DEFAULT_DATABASE)->event()
	 *
	 * @param string $event The class of event, e.g. "page_load".
	 * @param array $tags An array of tags to attach to the event, e.g. ["code" => 200].
	 */
	public function event(string $event, array $tags = []): void
	{
		$this->get()->event($event, $tags);
	}

	/**
	 * Record an increase in a counter.
	 * This is a shortcut for Instrument::get(Instrument::DEFAULT_DATABASE)->count()
	 *
	 * @param string $event The class of event, e.g. "page_load".
	 * @param array $tags An array of tags to attach to the event, e.g. ["code" => 200].
	 * @param float $increase The amount by which to increase the counter.
	 */
	public function count(string $event, array $tags = [], float $increase = 1): void
	{
		$this->get()->count($event, $tags, $increase);
	}

	/**
	 * Record the current value of a gauge.
	 * This is a shortcut for Instrument::get(Instrument::DEFAULT_DATABASE)->gauge()
	 *
	 * @param string $event The name of the gauge, e.g. "queue_length".
	 * @param array $tags An array of tags to attach to the event, e.g. ["datacentre" => "uk"].
	 * @param float $value The value of the gauge.
	 */
	public function gauge(string $event, array $tags, float $value): void
	{
		$this->get()->gauge($event, $tags, $value);
	}
}
