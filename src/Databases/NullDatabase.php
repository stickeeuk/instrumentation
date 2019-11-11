<?php
/**
 * The null database
 */

namespace Stickee\Instrumentation\Databases;

/**
 * This class discards metrics
 */
class NullDatabase implements DatabaseInterface
{
    /**
     * Set the error handler
     *
     * @param mixed $errorHandler An error handler function that takes an Exception as an argument - must be callable with `call_user_func()`
     */
    public function setErrorHandler($errorHandler): void
    {
        // Do nothing
    }

    /**
     * Record an event
     *
     * @param string $event The class of event, e.g. "page_load"
     * @param array $tags An array of tags to attach to the event, e.g. ["code" => 200]
     */
    public function event(string $event, array $tags = []): void
    {
        // Do nothing
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
        // Do nothing
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
        // Do nothing
    }
}
