<?php

return [
    /*
     |--------------------------------------------------------------------------
     | Database class
     |--------------------------------------------------------------------------
     |
     | The instrumentation database class name
     */
    'database' => env('INSTRUMENTATION_DATABASE', 'Stickee\Instrumentation\Databases\InfluxDb'),

    /*
     |--------------------------------------------------------------------------
     | DSN
     |--------------------------------------------------------------------------
     |
     | The instrumentation data source name, e.g.
     | https+influxdb://username:password@example.com:8086/database_name
     */
    'dsn' => env('INSTRUMENTATION_DSN'),
];
