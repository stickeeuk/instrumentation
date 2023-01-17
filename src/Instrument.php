<?php

namespace Stickee\Instrumentation;

use Stickee\Instrumentation\Databases\DatabaseInterface;

/**
 * The Instrument class records metrics
 * Use statically, e.g. Instrument::event(...)
 */
class Instrument implements DatabaseInterface
{
    /**
     * Constructor
     */
    private function __construct()
    {
        // Private to disallow object construction
    }

    /**
     * The database
     *
     * @var \Stickee\Instrumentation\Databases\DatabaseInterface $database
     */
    private static $database;

    /**
     * Assign a database to the class
     *
     * @param \Stickee\Instrumentation\Databases\DatabaseInterface $database The database
     */
    public static function setDatabase(DatabaseInterface $database)
    {
        self::$database = $database;
    }

    /**
     * Set the error handler
     *
     * @param mixed $errorHandler An error handler function that takes an Exception as an argument - must be callable with `call_user_func()`
     */
    public function setErrorHandler($errorHandler): void
    {
        self::$database->setErrorHandler($errorHandler);
    }

    /**
     * Returns the error handler.
     *
     * @return mixed
     */
    public function getErrorHandler()
    {
        return self::$database->getErrorHandler();
    }

    /**
     * Record an event
     *
     * @param string $event The class of event, e.g. "page_load"
     * @param array $tags An array of tags to attach to the event, e.g. ["code" => 200]
     */
    public function event(string $event, array $tags = []): void
    {
        self::$database->event($event, $tags);
    }

    /**
     * Record an increase in a counter
     *
     * @param string $event The class of event, e.g. "page_load"
     * @param array $tags An array of tags to attach to the event, e.g. ["code" => 200]
     * @param float $increase The amount by which to increase the counter
     */
    public function count(string $event, array $tags = [], float $increase = 1): void
    {
        self::$database->count($event, $tags, $increase);
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
        self::$database->gauge($event, $tags, $value);
    }

    /**
     * Flush any queued writes
     *
     * @return void
     */
    public function flush(): void
    {
        self::$database->flush();
    }
}
