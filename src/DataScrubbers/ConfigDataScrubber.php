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
     * The default config keys to be ignored regexes
     *
     * @var array<string>
     */
    public const array DEFAULT_CONFIG_KEY_IGNORE_REGEXES = [];

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
     * @param array<string> $configKeyIgnoreRegexes The config key regexes to ignore
     */
    public function __construct(
        private readonly array $configKeyRegexes,
        private readonly array $configKeyIgnoreRegexes
    ) {
        $config = config()->all();

        unset($config['auth'], $config['app']['aliases'], $config['instrumentation']['scrubbing']);

        $config = Arr::dot($config);

        // Filter out non-string values and values less than 8 characters long.
        // Assume that any secrets are at least 8 characters long. This prevents situations where,
        // for example, a single-character config value is redacted.
        $config = array_filter($config, function ($value, $key): bool {
            return is_string($value) && (mb_strlen($value) >= 8)
                && $this->shouldScrub((string) $key);
        }, ARRAY_FILTER_USE_BOTH);

        foreach (array_keys($config) as $configKey) {
            $this->regexes['/' . preg_quote($config[$configKey], '/') . '/'] = '[REDACTED_CONFIG_VALUE:' . $configKey . ']';
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

    /**
     * Should the value be scrubbed?
     *
     * @param string $key The key
     */
    private function shouldScrub(string $key): bool
    {
        foreach ($this->configKeyRegexes as $pattern) {
            if (preg_match($pattern, $key)) {
                foreach ($this->configKeyIgnoreRegexes as $ignorePattern) {
                    if (preg_match($ignorePattern, $key)) {
                        return false;
                    }
                }

                return true;
            }
        }

        return false;
    }
}
