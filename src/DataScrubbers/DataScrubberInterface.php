<?php

declare(strict_types=1);

namespace Stickee\Instrumentation\DataScrubbers;

interface DataScrubberInterface
{
    public function scrub($key, $value);
}
