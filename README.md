# Stickee Instrumentation

This a Composer module for recording metrics.

## Installation

```bash
composer require stickee/instrumentation
```

## Configuration

### Basic Usage

To use the basic features, you must create an instrumentation database and record events to it.

```php
use Stickee\Instrumentation\Databases\InfluxDb;

// Create the database
$database = new InfluxDb('influxdb://username:password@example.com:8086/database_name');

// Log an event
$database->event('some_event');
```

### Static Accessor (Non-Laravel projects, for Laravel use the Facade)

You can access your database statically by assigning it to the `Instrument` class.

```php
use Stickee\Instrumentation\Databases\InfluxDb;
use Stickee\Instrumentation\Instrument;

// Create the database
$database = new InfluxDb('https+influxdb://username:password@example.com:443/database_name');

// Assign to the Instrument class
Instrument::setDatabase($database);

// Log an event
Instrument::event('some_event');
```

### Event Types

There are 3 event type methods defined in the `DatabaseInterface` interface.

| Event                                                                    | Arguments                                                                                                 | Description                         |
|--------------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------|-------------------------------------|
| `$database->event(string $name, array $tags = [], float value = 1)`      | `$name` The event name<br>`$tags` An array of tags                                                        | Record a single event               |
| `$database->count(string $event, array $tags = [], float $increase = 1)` | `$name` The counter name<br>`$tags` An array of tags<br>`$increase` The amount to increase the counter by | Record an increase in a counter     |
| `$database->gauge(string $event, array $tags = [], float $value)         | `$name` The gauge name<br>`$tags` An array of tags<br>`$value` The value to record                        | Record the current value of a gauge |

Tags should be an associative array of `tag_name` => `tag_value`, e.g.

```php
$tags = ['datacentre' => 'uk', 'http_status' => \Symfony\Component\HttpFoundation\Response::HTTP_OK];
```

### Errors

In the event of an error an exception will be thrown. If you want to catch all
instrumentation exceptions and pass them through your own error handler, you can
call `setErrorHandler` on the database with a callback that accepts an
`\Exception` as a parameter.

```php
use Exception;

$database->setErrorHandler(function (Exception $e) {
    report($e);
});
```

## Databases

Databases are classes implementing the `Stickee\Instrumentation\Databases\DatabaseInterface` interface.
This module ships with the following classes:

| Class         | Description                                                                             |
|---------------|-----------------------------------------------------------------------------------------|
| OpenTelemetry | Writes to an [OpenTelemetry Collector](https://opentelemetry.io/docs/collector/)        |
| InfluxDb      | Writes to [InfluxDB](https://www.influxdata.com/products/influxdb-overview/)            |
| LaravelDump   | Uses the [Laravel `dump()`](https://laravel.com/docs/master/helpers#method-dump) helper |
| LaravelLog    | Writes to the [Laravel `Log`](https://laravel.com/docs/master/logging)                  |
| Log           | Writes to a log file                                                                    |
| NullDatabase  | Discards all data                                                                       |

**Note:** Only `OpenTelemetry` and `InfluxDb` are recommended for production use.
The others are for development / debugging.

## Laravel

### Installation

First install with Composer as normal:

```bash
composer require stickee/instrumentation
```

This module ships with a Laravel service provider and facade, which will be automatically registered for Laravel 5.5+.

If you're using InfluxDb then you can simply set `INSTRUMENTATION_DATABASE` and `INSTRUMENTATION_DSN` in your .env file
(see Configuration below) then use the facade in your code - no further
configuration is necessary:

```php
use Instrument;

Instrument::event('Hello World');
```

#### Manual registration

The module can be manually registered by adding this to the `providers` array in `config/app.php`:

```php
Stickee\Instrumentation\Laravel\ServiceProvider::class,
```

If you want to use the `Instrument` facade, add this to the `facades` array in `config/app.php`:

```php
'Instrument' => Stickee\Instrumentation\Laravel\Facade::class,
```

### Configuration

The configuration defaults to the `NullDatabase`. To change this, set `INSTRUMENTATION_DATABASE`
in your `.env` and add any other required variables.

| Class         | `INSTRUMENTATION_DATABASE` Value                       | Other Values                                                                                                                                                                                       |
|---------------|--------------------------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| OpenTelemetry | `"Stickee\\Instrumentation\\Databases\\OpenTelemetry"` | `INSTRUMENTATION_DSN="http://example.com:4318"` - The OpenTelemetry Collector URL                                                                                                                  |
| InfluxDb      | `"Stickee\\Instrumentation\\Databases\\InfluxDb"`      | `INSTRUMENTATION_DSN="https+influxdb://username:password@example.com:8086/database_name"` - The database DSN (required)<br>`INSTRUMENTATION_VERIFY_SSL=true` - Verify the database SSL certificate |
| LaravelDump   | `"Stickee\\Instrumentation\\Databases\\LaravelDump"`   | None                                                                                                                                                                                               |
| LaravelLog    | `"Stickee\\Instrumentation\\Databases\\LaravelLog"`    | None                                                                                                                                                                                               |
| Log           | `"Stickee\\Instrumentation\\Databases\\Log"`           | `INSTRUMENTATION_FILENAME="/path/to/file.log"` - The log file (required)                                                                                                                           |
| NullDatabase  | `"Stickee\\Instrumentation\\Databases\\NullDatabase"`  | None                                                                                                                                                                                               |

If you wish to, you can copy the package config to your local config with the publish command,
however this is **unnecessary** in normal usage:

```bash
php artisan vendor:publish --provider="Stickee\Instrumentation\Laravel\ServiceProvider"
```

### Using Open Telemetry

 - Install OpenTelemetry packages: `composer require open-telemetry/exporter-otlp:1.0.0beta-12 open-telemetry/opentelemetry-logger-monolog:^0.0.2 google/protobuf`
 - Publish the OpenTelemetry config: `php artisan vendor:publish --provider="PlunkettScott\LaravelOpenTelemetry\OtelServiceProvider" --tag=otel-config`
 - Recommended - change `OTEL_ENABLED` to `INSTRUMENTATION_ENABLED`
 - Set the required .env variables `INSTRUMENTATION_DATABASE` and `INSTRUMENTATION_DSN`

### Using InfluxDb

 - Install the InfluxDB PHP client: `composer require influxdb/influxdb-php`
 - Set the required .env variables `INSTRUMENTATION_DATABASE` and `INSTRUMENTATION_DSN`

### Using a Custom Database

If you wish to use a custom database class for `INSTRUMENTATION_DATABASE` then you simply need to implement `Stickee\Instrumentation\Databases\DatabaseInterface` and make sure it is constructable by the service container.

## Developing

The easiest way to make changes is to make the project you're importing the module in to load the module from your filesystem instead of the Composer repository, like this:

1. `composer remove stickee/instrumentation`
2. Edit `composer.json` and add
    ```
    "repositories": [
            {
                "type": "path",
                "url": "../instrumentation"
            }
        ]
    ```
    where "../instrumentation" is the path to where you have this project checked out.
3. `composer require stickee/instrumentation`

**NOTE:** Do not check in your `composer.json` like this!

## Testing

Tests are written using the Pest testing framework and use Orchestra testbench to emulate a Laravel environment. To
ensure a wide range of compatibility, these are run via GitHub Actions for a supported matrix of PHP, operating system,
and Laravel versions.

You can run tests on your own system by invoking Pest:

```bash
./vendor/bin/pest
```

### Databases and Visualisation

#### OpenTelemetry

Go to `./vendor/stickee/instrumentation/docker/opentelemetry` and run `docker compose up`.
This will start Grafana, Loki, Tempo InfluxDB, and the OpenTelemetry Collector and expose them on your local machine.

 - Grafana: http://localhost:3000
 - OpenTelemetry Collector: http://localhost:4318 (this should be used for `INSTRUMENTATION_DSN`)

### InfluxDB

Go to `./vendor/stickee/instrumentation/docker/influxdb` and run `docker compose up`.
This will start Chronograf and InfluxDB and expose them on your local machine.

 - Chronograf: http://localhost:8888
 - InfluxDB: http://localhost:8086 (this should be used for `INSTRUMENTATION_DSN`)

## Contributions

Contributions are welcome to all areas of the project, but please provide tests. Code style will be checked using
automatically checked via [Stickee Canary](https://github.com/stickeeuk/canary) on your pull request. You can however
install it locally using instructions on the above link.

## License

Instrumentation is open source software licensed under the [MIT license](https://opensource.org/licenses/MIT).
