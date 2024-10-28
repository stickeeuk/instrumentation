<?php

declare(strict_types=1);

namespace Stickee\Instrumentation\DataScrubbers;

use Illuminate\Support\Arr;

class ConfigDataScrubber implements DataScrubberInterface
{
    /**
     * The default config key regexes
     *
     * @var array<string>
     */
    public const array DEFAULT_CONFIG_KEY_REGEXES = [
        '/auth|key|pass|secret|signature|token/i',
    ];

    /**
     * The regexes to use
     *
     * @var array<string, string>
     */
    private array $regexes = [];

    /**
     * Constructor
     *
     * @param array<string> $configKeyRegexes The config key regexes to redact values for
     */
    public function __construct(array $configKeyRegexes)
    {
        $config = config()->all();

        unset($config['auth'], $config['app']['aliases'], $config['instrumentation']['scrubbing']);

        $config = Arr::dot($config);
        $config = array_filter($config, fn($value) => is_string($value) && ($value !== ''));

        foreach (array_keys($config) as $configKey) {
            foreach ($configKeyRegexes as $pattern) {
                if (preg_match($pattern, $configKey)) {
                    $this->regexes['/' . preg_quote($config[$configKey], '/') . '/'] = '[REDACTED_CONFIG_VALUE:' . $configKey . ']';
                }
            }
        }
    }

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

        foreach ($this->regexes as $regex => $replacement) {
            $value = preg_replace($regex, $replacement, (string) $value);
        }

        return $value;
    }
}
