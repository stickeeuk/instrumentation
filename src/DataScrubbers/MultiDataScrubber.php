<?php

declare(strict_types=1);

namespace Stickee\Instrumentation\DataScrubbers;

class MultiDataScrubber implements DataScrubberInterface
{
    /**
     * Constructor
     *
     * @param array<DataScrubberInterface> $dataScrubbers The data scrubbers
     */
    public function __construct(private readonly array $dataScrubbers) {}

    /**
     * Scrub data
     *
     * @param mixed $key The key
     * @param mixed $value The value
     */
    #[\Override]
    public function scrub(mixed $key, mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        foreach ($this->dataScrubbers as $dataScrubber) {
            $value = $dataScrubber->scrub($key, $value);
        }

        return $value;
    }
}
