<?php

declare(strict_types=1);

namespace Stickee\Instrumentation\DataScrubbers;

class CallbackDataScrubber implements DataScrubberInterface
{
    /**
     * Constructor
     *
     * @param callable(mixed $key, mixed $value): mixed $callback The data scrubbing callback
     */
    public function __construct(private readonly callable $callback) {}

    /**
     * Scrub data
     *
     * @param mixed $key The key
     * @param mixed $value The value
     */
    public function scrub(mixed $key, mixed $value): mixed
    {
        return ($this->callback)($key, $value);
    }
}
