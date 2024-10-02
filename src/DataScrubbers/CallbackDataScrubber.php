<?php

declare(strict_types=1);

namespace Stickee\Instrumentation\DataScrubbers;

class CallbackDataScrubber implements DataScrubberInterface
{
    public function __construct(private readonly callable $callback)
    {
    }

    public function scrub($key, $value)
    {
        return ($this->callback)($key, $value);
    }
}
