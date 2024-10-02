<?php

declare(strict_types=1);

namespace Stickee\Instrumentation\DataScrubbers;

class DefaultDataScrubber implements DataScrubberInterface
{
    public function scrub($key, $value)
    {
        return $value;
    }
}
