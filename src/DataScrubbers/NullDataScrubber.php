<?php

declare(strict_types=1);

namespace Stickee\Instrumentation\DataScrubbers;

class NullDataScrubber implements DataScrubberInterface
{
    /**
     * Scrub data
     *
     * @param mixed $key The key
     * @param mixed $value The value
     */
    public function scrub(mixed $key, mixed $value): mixed
    {
        return $value;
    }
}
