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

/**
 * The Instrument class records metrics.
 */
interface InstrumentInterface
{
	/**
	 * Add a database to which metrics can be written.
	 *
	 * @param InstrumentationInterface $database The database to add.
	 * @param string $name The name of the database, for use with `get()` or null to use the default.
	 *
	 * @throws InvalidArgumentException Throws if `$name` has already been added.
	 */
	public function add(DatabaseInterface $database, string $name = Instrument::DEFAULT_DATABASE): void;

	/**
	 * Get a database by name.
	 *
	 * @param string $name The name of the database to get.  Empty string for default.
	 */
	public function get(string $name = Instrument::DEFAULT_DATABASE): DatabaseInterface;
}
