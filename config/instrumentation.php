<?php

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
    'events_exporter' => env('INSTRUMENTATION_EVENTS_EXPORTER', 'Stickee\\Instrumentation\\Exporters\\Events\\NullEvents'),

    /*
     |--------------------------------------------------------------------------
     | Spans exporter class
     |--------------------------------------------------------------------------
     |
     | The instrumentation spans exporter class name
     */
    'spans_exporter' => env('INSTRUMENTATION_SPANS_EXPORTER', 'Stickee\\Instrumentation\\Exporters\\Spans\\NullSpans'),

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
        'dsn' => env('INSTRUMENTATION_OPENTELEMETRY_DSN', 'http://localhost:4318'),
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
     | Queue Names
     |--------------------------------------------------------------------------
     |
     | An array of queue names to monitor
     */
    'queue_names' => array_map('trim', explode(',', env('INSTRUMENTATION_QUEUE_NAMES', 'default'))),
];
