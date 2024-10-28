<?php

use Stickee\Instrumentation\DataScrubbers\ConfigDataScrubber;
use Stickee\Instrumentation\DataScrubbers\RegexDataScrubber;
use Stickee\Instrumentation\Exporters\Events\NullEvents;
use Stickee\Instrumentation\Exporters\Events\OpenTelemetry as OpenTelemetryEvents;
use Stickee\Instrumentation\Exporters\Spans\NullSpans;
use Stickee\Instrumentation\Exporters\Spans\OpenTelemetry as OpenTelemetrySpans;

$isProduction = env('APP_ENV', 'production') === 'production';

return [
    /*
     |--------------------------------------------------------------------------
     | Enable instrumentation
     |--------------------------------------------------------------------------
     |
     | true / false (NullEvents and NullSpans will be used if not enabled)
     */
    'enabled' => env('INSTRUMENTATION_ENABLED', true),

    /*
     |--------------------------------------------------------------------------
     | Events exporter class
     |--------------------------------------------------------------------------
     |
     | The instrumentation events exporter class name
     */
    'events_exporter' => env('INSTRUMENTATION_EVENTS_EXPORTER', $isProduction ? OpenTelemetryEvents::class : NullEvents::class),

    /*
     |--------------------------------------------------------------------------
     | Spans exporter class
     |--------------------------------------------------------------------------
     |
     | The instrumentation spans exporter class name
     */
    'spans_exporter' => env('INSTRUMENTATION_SPANS_EXPORTER', $isProduction ? OpenTelemetrySpans::class : NullSpans::class),

    /*
     |--------------------------------------------------------------------------
     | InfluxDB
     |--------------------------------------------------------------------------
     |
     | Configuration for InfluxDb
     */
    'influxdb' => [
        'url' => env('INSTRUMENTATION_INFLUXDB_URL', 'http://localhost:8086'),
        'token' => env('INSTRUMENTATION_INFLUXDB_TOKEN', 'my-super-secret-auth-token'),
        'bucket' => env('INSTRUMENTATION_INFLUXDB_BUCKET', 'test'),
        'org' => env('INSTRUMENTATION_INFLUXDB_ORG', 'stickee'),
        'verify_ssl' => env('INSTRUMENTATION_INFLUXDB_VERIFY_SSL', false),
    ],

    /*
     |--------------------------------------------------------------------------
     | OpenTelemetry
     |--------------------------------------------------------------------------
     |
     | Configuration for OpenTelemetry
     */
    'opentelemetry' => [
        'dsn' => env('INSTRUMENTATION_OPENTELEMETRY_DSN', env('OTEL_EXPORTER_OTLP_ENDPOINT') ?: 'http://localhost:4318'),
    ],

    /*
     |--------------------------------------------------------------------------
     | Log file
     |--------------------------------------------------------------------------
     |
     | Configuration for LogFile
     */
    'log_file' => [
        'filename' => env('INSTRUMENTATION_LOG_FILE_FILENAME', 'instrumentation.log'),
    ],

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
