<?php

declare(strict_types=1);

namespace Stickee\Instrumentation\DataScrubbers;

class DefaultDataScrubber implements DataScrubberInterface
{
    /**
     * The default email regex
     */
    public const string EMAIL_REGEX = '/(?:[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+)*|"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:(2(5[0-5]|[0-4][0-9])|1[0-9][0-9]|[1-9]?[0-9]))\.){3}(?:(2(5[0-5]|[0-4][0-9])|1[0-9][0-9]|[1-9]?[0-9])|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])/';

    /**
     * The default UK postcode regex
     */
    public const string UK_POSTCODE_REGEX = '/\b(([A-Z]{1,2}\d[A-Z\d]?|ASCN|STHL|TDCU|BBND|[BFS]IQQ|PCRN|TKCA) ?\d[A-Z]{2}|BFPO ?\d{1,4}|(KY\d|MSR|VG|AI)[ -]?\d{4}|[A-Z]{2} ?\d{2}|GE ?CX|GIR ?0A{2}|SAN ?TA1)\b/i';

    /**
     * The default redactions
     */
    public const array DEFAULT_REDACTIONS = [
        self::EMAIL_REGEX => '[REDACTED_EMAIL]',
        self::UK_POSTCODE_REGEX => '[REDACTED_UK_POSTCODE]',
    ];

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

        foreach (self::DEFAULT_REDACTIONS as $regex => $replacement) {
            $value = preg_replace($regex, $replacement, (string) $value);
        }

        return $value;
    }
}
