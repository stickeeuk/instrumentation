<?php

namespace Stickee\Instrumentation\Exporters\Events\Traits;

/**
 * Writes events as strings
 */
trait WritesStrings
{
    /**
     * Record an event
     *
     * @param string $name The name of the event, e.g. "page_load_time"
     * @param array $attributes An array of attributes to attach to the event, e.g. ["code" => 200]
     * @param float $value The value of the event, e.g. 12.3
     */
    public function event(string $name, array $attributes = [], float $value = 1): void
    {
        $message = date('Y-m-d H:i:s') . ' EVENT: ' . $name
            . ' = ' . $value
            . $this->getAttributesString($attributes);

        $this->write($message);
    }

    /**
     * Record an increase in a counter
     *
     * @param string $name The counter name, e.g. "page_load"
     * @param array $attributes An array of attributes to attach to the event, e.g. ["code" => 200]
     * @param float $increase The amount by which to increase the counter
     */
    public function counter(string $name, array $attributes = [], float $increase = 1): void
    {
        $message = date('Y-m-d H:i:s') . ' COUNTER: ' . $name . ' += ' . $increase
            . $this->getAttributesString($attributes);

        $this->write($message);
    }

    /**
     * Record the current value of a gauge
     *
     * @param string $name The name of the gauge, e.g. "queue_length"
     * @param array $attributes An array of attributes to attach to the event, e.g. ["datacentre" => "uk"]
     * @param float $value The value of the gauge
     */
    public function gauge(string $name, array $attributes, float $value): void
    {
        $message = date('Y-m-d H:i:s') . ' GAUGE: ' . $name . ' = ' . $value
            . $this->getAttributesString($attributes);

        $this->write($message);
    }

    /**
     * Record a value on a histogram
     *
     * @param string $name The name of the histogram, e.g. "http.server.duration"
     * @param string|null $unit The unit of the histogram, e.g. "ms"
     * @param string|null $description A description of the histogram
     * @param array $buckets A set of buckets, e.g. [0.25, 0.5, 1, 5]
     * @param float|int $value The value of the histogram
     * @param array $attributes An array of attributes to attach to the event, e.g. ["datacentre" => "uk"]
     */
    public function histogram(string $name, ?string $unit, ?string $description, array $buckets, float|int $value, array $attributes = []): void
    {
        $message = date('Y-m-d H:i:s') . ' HISTOGRAM: ' . $name . ' = ' . $value . $unit
            . $this->getAttributesString($attributes);

        $this->write($message);
    }

    /**
     * Flush any queued writes
     */
    public function flush(): void
    {
        // Do nothing - writes are not queued
    }

    /**
     * Write to the file
     *
     * @param string $message The message to write
     */
    abstract protected function write(string $message): void;

    /**
     * Convert the attributes array to a string
     *
     * @param array $attributes The attributes
     *
     * @return string The attributes string
     */
    private function getAttributesString(array $attributes): string
    {
        if (empty($attributes)) {
            return '';
        }

        $attributesStrings = [];

        foreach ($attributes as $key => $value) {
            if ($value === true) {
                $value = 'true';
            } elseif ($value === false) {
                $value = 'false';
            }

            $attributesStrings[] = $key . ' => ' . $value;
        }

        return ': [' . implode(', ', $attributesStrings) . ']';
    }
}
