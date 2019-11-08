# Stickee Instrumentation

This a composer module for recording metrics.

## Installation

`composer require stickee/instrumentation`

## Configuration

### Basic Usage

To use the basic features, you must create an instrumentation database and record events to it.

```
use Stickee\Instrumentation\Databases\InfluxDb;

// Create the database
$database = new InfluxDb('influxdb://username:password@example.com:8086/database_name');

// Log an event
$database->event('some_event');
```

### Static Accessor

You can access your database statically by assigning it to the `Instrument` class.

```
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

| Event | Arguments | Description |
| ----- | --------- | ----------- |
| `$database->event(string $name, array $tags = [])` | `$name` The event name<br>`$tags` An array of tags | Record a single event |
| `$database->count(string $event, array $tags = [], float $increase = 1)` | `$name` The counter name<br>`$tags`  An array of tags | Record an increase in a counter |
| `$database->gauge(string $event, array $tags = [], float $value)` | `$name` The gauge name<br>`$tags` An array of tags | Record the current value of a gauge |

Tags should be an associative array of `tag_name` => `tag_value`, e.g.

```
$tags = ['datacentre' => 'uk'];
```

### Errors

In the event of an error an exception will be throw. If you want to catch all
instrumentation exceptions and pass hem through your own error handler, you can
call `setErrorHandler` on the database with a callback that accepts an
`\Exception` as a parameter.

```
use Exception;

$database->setErrorHandler(function (Exception $e) {
    report($e);
});
```

## Laravel

### Installation

First install with Composer as normal:

```
composer require stickee/instrumentation
```

This module ships with a Laravel service provider for use with a database class that accepts a DSN in its constructor;
for example, the InfluxDb database.
The service provider and facade will be automatically registered for Laravel 5.5+.

If you're using InfluxDb then you can simply add a DSN to your .env file
(See Configuration below) then use the facade in your code - no further
configuration is necessary:

```
use Instrument;

Instrument::event('Hello World');
```

#### Manual registration

The module can be manually registered by adding this to the `providers` array in `config/app.php`:

```
Stickee\Instrumentation\Laravel\ServiceProvider::class,
```

If you want to use the `Instrument` facade, add this to the `facades` array in `config/app.php`:

```
'Instrument' => Stickee\Instrumentation\Laravel\Facade::class,
```

This will automatically create the `Database` and register it with the `Instrument` static accessor.

### Configuration

Add the following to your `.env` file:

```
INSTRUMENTATION_DSN="https+influxdb://username:password@example.com:443/database_name"
```

If you wish to, you can copy the package config to your local config with the publish command,
however this is **not** necessary in normal usage:

```
php artisan vendor:publish --provider="Stickee\Instrumentation\Laravel\ServiceProvider"
```

## Developing

The easiest way to make changes is to make the project you're importing the module in to load the module from your filesystem instead of the composer repository, like this:

1. `composer remove stickee/instrumentation`
2. Edit `composer.json` and add
    ```
    "repositories" : [
            {
                "type": "path",
                "url": "../instrumentation"
            }
        ]
    ```
    where "../instrumentation" is the path to where you have this project checked out
3. `composer require stickee/instrumentation`

*NOTE:* Do not check in your `composer.json` like this!
