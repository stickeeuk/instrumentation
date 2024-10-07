<?php

declare(strict_types=1);

namespace Stickee\Instrumentation\DataScrubbers;

use Closure;

class CallbackDataScrubber implements DataScrubberInterface
{
    /**
     * Constructor
     *
     * @param Closure(mixed $key, mixed $value): mixed $callback The data scrubbing callback
     */
    public function __construct(private readonly Closure $callback) {}

    /**
     * Scrub data
     *
     * @param mixed $key The key
     * @param mixed $value The value
     */
    #[\Override]
    public function scrub(mixed $key, mixed $value): mixed
    {
        return ($this->callback)($key, $value);
    }
}
