<?php

namespace Stickee\Instrumentation\Databases\Traits;

/**
 * Writes events as strings
 */
trait WritesStrings
{
    /**
     * Write to the file
     *
     * @param string $message The message to write
     */
    abstract protected function write(string $message): void;

    /**
     * Convert the tags array to a string
     *
     * @param array $tags The tags
     *
     * @return string The tags string
     */
    private function getTagsString(array $tags): string
    {
        if (empty($tags)) {
            return '';
        }

        $tagStrings = [];

        foreach ($tags as $key => $value) {
            if ($value === true) {
                $value = 'true';
            } elseif ($value === false) {
                $value = 'false';
            }

            $tagStrings[] = $key . ' => ' . $value;
        }

        return ': [' . implode(', ', $tagStrings) . ']';
    }

    /**
     * Record an event
     *
     * @param string $event The class of event, e.g. "page_load"
     * @param array $tags An array of tags to attach to the event, e.g. ["code" => 200]
     */
    public function event(string $event, array $tags = []): void
    {
        $message = date('Y-m-d H:i:s') . ' EVENT: ' . $event
            . $this->getTagsString($tags);

        $this->write($message);
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
        $message = date('Y-m-d H:i:s') . ' COUNT: ' . $event . ' += ' . $increase
            . $this->getTagsString($tags);

        $this->write($message);
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
        $message = date('Y-m-d H:i:s') . ' GAUGE: ' . $event . ' = ' . $value
            . $this->getTagsString($tags);

        $this->write($message);
    }

    /**
     * Flush any queued writes
     */
    public function flush(): void
    {
        // Do nothing - writes are not queued
    }
}
