<?php

use Stickee\Instrumentation\DataScrubbers\ConfigDataScrubber;
use Stickee\Instrumentation\DataScrubbers\RegexDataScrubber;

return [
    /*
     |--------------------------------------------------------------------------
     | Response Time Middleware
     |--------------------------------------------------------------------------
     |
     | Enable the automatic response time instrumentation middleware
     */
    'response_time_middleware_enabled' => env('INSTRUMENTATION_RESPONSE_TIME_MIDDLEWARE_ENABLED', true),

    /*
     |--------------------------------------------------------------------------
     | Trace Sample Rate
     |--------------------------------------------------------------------------
     |
     | 0 = never sample, 1 = always sample
     */
    'trace_sample_rate' => env('INSTRUMENTATION_TRACE_SAMPLE_RATE', 1),

    /*
     |--------------------------------------------------------------------------
     | Long Request Trace Threshold
     |--------------------------------------------------------------------------
     |
     | The time in seconds after which a trace should always be sampled (0 to disable)
     */
    'long_request_trace_threshold' => env('INSTRUMENTATION_LONG_REQUEST_TRACE_THRESHOLD', 1),

    /*
     |--------------------------------------------------------------------------
     | Queue Names
     |--------------------------------------------------------------------------
     |
     | An array of queue names to monitor
     */
    'queue_names' => array_map('trim', explode(',', env('INSTRUMENTATION_QUEUE_NAMES', 'default'))),

    'scrubbing' => [
        /*
         |--------------------------------------------------------------------------
         | Scrubbing regexes
         |--------------------------------------------------------------------------
         |
         | A map of regex => replacement for scrubbing data
         */
        'regexes' => env('INSTRUMENTATION_SCRUBBING_REGEXES') === null
            ? RegexDataScrubber::DEFAULT_REGEX_REPLACEMENTS
            : array_map('trim', explode(',', env('INSTRUMENTATION_SCRUBBING_REGEXES'))),

        /*
         |--------------------------------------------------------------------------
         | Scrubbing config key regexes
         |--------------------------------------------------------------------------
         |
         | An array of regexes. Matching config keys will have their values scrubbed
         */
        'config_key_regexes' => env('INSTRUMENTATION_SCRUBBING_CONFIG_KEY_REGEXES') === null
            ? ConfigDataScrubber::DEFAULT_CONFIG_KEY_REGEXES
            : array_map('trim', explode(',', env('INSTRUMENTATION_SCRUBBING_CONFIG_KEY_REGEXES'))),
    ],
];
