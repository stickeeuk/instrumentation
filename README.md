# Instrumentation BY ZEBRA NORTH

This a composer module for recording metrics.

## Installation

`composer require stickee/instrumentation`

## Configuration

### Basic Usage

To use the basic features, you must create an instrumentation database and record events to it.

```
use Stickee\Instrumentation\Databases\InfluxDb;

// Create the database
$database = new InfluxDb('https+influxdb://username:password@example.com:8086/database_name');

// Log an event
$database->event('some_event');
```

### Static Accessor

You can access your database statically by assigning it to the `Instrument` class.

```
use Stickee\Instrumentation\Databases\InfluxDb;
use Stickee\Instrumentation\Instrument;

// Create the database
$database = new InfluxDb('https+influxdb://username:password@example.com:8086/database_name');

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
