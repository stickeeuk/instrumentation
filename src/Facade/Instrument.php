<?php

namespace Stickee\Instrumentation\Facade;

use Stickee\Instrumentation\Databases\DatabaseInterface;
use Stickee\Instrumentation\Instrument as ConcreteInstrument;
use Stickee\Instrumentation\InstrumentInterface;

/**
 * The Instrument class records metrics.
 */
class Instrument implements DatabaseInterface, InstrumentInterface
{
	private static $instrument;

	public static function setInstrument(DatabaseInterface $instrument)
	{
		self::$instrument = $instrument;
	}

	/**
	 * Add a database to which metrics can be written.
	 *
	 * @param DatabaseInterface $database The database to add.
	 * @param string $name The name of the database, for use with `get()` or null to use the default.
	 *
	 * @throws InvalidArgumentException Throws if `$name` has already been added.
	 */
	public function add(DatabaseInterface $database, string $name = ConcreteInstrument::DEFAULT_DATABASE): void
	{
		self::$instrument->add($database, $name);
	}

	/**
	 * Get a database by name.
	 *
	 * @param string $name The name of the database to get.  Empty string for default.
	 */
	public function get(string $name = ConcreteInstrument::DEFAULT_DATABASE): DatabaseInterface
	{
		return self::$instrument->get($name);
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
		self::get()->event($event, $tags);
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
		self::get()->count($event, $tags, $increase);
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
		self::get()->gauge($event, $tags, $value);
	}
}
