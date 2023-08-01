<?php

return [
    /*
     |--------------------------------------------------------------------------
     | Enable instrumentation
     |--------------------------------------------------------------------------
     |
     | true / false (NullDatabase will be used if not enabled)
     */
    'enabled' => env('INSTRUMENTATION_ENABLED', true),

    /*
     |--------------------------------------------------------------------------
     | Database class
     |--------------------------------------------------------------------------
     |
     | The instrumentation database class name
     */
    'database' => env('INSTRUMENTATION_DATABASE', 'Stickee\Instrumentation\Databases\NullDatabase'),

    /*
     |--------------------------------------------------------------------------
     | DSN
     |--------------------------------------------------------------------------
     |
     | The instrumentation data source name for dsn-based databases e.g.
     | https+influxdb://username:password@example.com:8086/database_name
     */
    'dsn' => env('INSTRUMENTATION_DSN'),

    /*
     |--------------------------------------------------------------------------
     | Verify SSL
     |--------------------------------------------------------------------------
     |
     | Verify the database SSL certificate if using HTTPS
     */
    'verifySsl' => env('INSTRUMENTATION_VERIFY_SSL', true),

    /*
     |--------------------------------------------------------------------------
     | Filename
     |--------------------------------------------------------------------------
     |
     | The log file name for file-based databases e.g.
     | storage_path('app/instrument.log')
     */
    'filename' => env('INSTRUMENTATION_FILENAME'),

    /*
     |--------------------------------------------------------------------------
     | Response Time Middleware
     |--------------------------------------------------------------------------
     |
     | Enable the automatic response time instrumentation middleware
     */
    'response_time_middleware_enabled' => env('INSTRUMENTATION_RESPONSE_TIME_MIDDLEWARE_ENABLED', true),
];
