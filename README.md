# Stickee Instrumentation

This a Composer module for recording metrics.

## Non-Laravel projects (For Laravel see below)

### Installation

```bash
composer require stickee/instrumentation
```

### Configuration

#### Basic Usage
To use the basic features, you must create an instrumentation exporter and record events to it.
This requires an Event exporter and a Span exporter.

```php
use Stickee\Instrumentation\Exporters\Exporter;
use Stickee\Instrumentation\Exporters\Events\LogFile;
use Stickee\Instrumentation\Exporters\Spans\NullSpan;

// Create the exporter
$eventsExporter = new LogFile('/path/to/file.log');
$spansExporter = new NullSpan();
$exporter = new Exporter($eventsExporter, $spansExporter);

// Log an event
$exporter->event('some_event');
```

#### Static Accessor

You can access your exporter statically by assigning it to the `Instrument` class.

```php
use Stickee\Instrumentation\Exporters\Exporter;
use Stickee\Instrumentation\Exporters\Events\LogFile;
use Stickee\Instrumentation\Exporters\Spans\NullSpan;
use Stickee\Instrumentation\Instrument;

// Create the exporter
$exporter = new Exporter(new LogFile('/path/to/file.log'), new NullSpan());

// Assign to the Instrument class
Instrument::setExporter($exporter);

// Log an event
Instrument::event('some_event');
```

#### Errors

In the event of an error an exception will be thrown. If you want to catch all
instrumentation exceptions and pass them through your own error handler, you can
call `setErrorHandler` on the database with a callback that accepts an
`\Exception` as a parameter.

```php
use Exception;

$exporter->setErrorHandler(function (Exception $e) {
    report($e);
});
```

### Event Exporters

Event exporters are classes implementing the `Stickee\Instrumentation\Exporters\Interfaces\EventsExporterInterface` interface.
This module ships with the following classes:

| Class         | Description                                                                             |
|---------------|-----------------------------------------------------------------------------------------|
| OpenTelemetry | Writes to an [OpenTelemetry Collector](https://opentelemetry.io/docs/collector/)        |
| InfluxDb      | Writes to [InfluxDB](https://www.influxdata.com/products/influxdb-overview/)            |
| LaravelDump   | Uses the [Laravel `dump()`](https://laravel.com/docs/master/helpers#method-dump) helper |
| LaravelLog    | Writes to the [Laravel `Log`](https://laravel.com/docs/master/logging)                  |
| LogFile       | Writes to a log file                                                                    |
| NullEvents    | Discards all data                                                                       |

**Note:** Only `OpenTelemetry` and `InfluxDb` are recommended for production use.
The others are for development / debugging.

## Span Exporters

Span exporters are classes implementing the `Stickee\Instrumentation\Exporters\Interfaces\SpansExporterInterface` interface.
This module ships with the following classes:

| Class         | Description                                                                      |
|---------------|----------------------------------------------------------------------------------|
| OpenTelemetry | Writes to an [OpenTelemetry Collector](https://opentelemetry.io/docs/collector/) |
| NullSpans     | Discards all data                                                                |

## Laravel Projects

### Installation

First install with Composer as normal:

```bash
composer require stickee/instrumentation
```

This module ships with a Laravel service provider and facade, which will be automatically registered.
Follow the configurations steps below to configure the module, then you can use the `Instrument` facade:

```php
use Instrument;

Instrument::event('Hello World');
```

### Manual registration

The module can be manually registered by adding this to the `providers` array in `config/app.php`:

```php
Stickee\Instrumentation\Laravel\ServiceProvider::class,
```

If you want to use the `Instrument` facade, add this to the `facades` array in `config/app.php`:

```php
'Instrument' => Stickee\Instrumentation\Laravel\Facade::class,
```

### Configuration

The Events exporter defaults to `NullEvents`. To change this, set `INSTRUMENTATION_EVENTS_EXPORTER`
in your `.env` and add any other required variables.

| Class         | `INSTRUMENTATION_EVENTS_EXPORTER` Value                        | Other Values                                                                                                                                                                                                                                                                                                                                                                                  |
|---------------|----------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| OpenTelemetry | `"Stickee\\Instrumentation\\Exporters\\Events\\OpenTelemetry"` | `INSTRUMENTATION_OPENTELEMETRY_DSN="http://example.com:4318"` - The OpenTelemetry Collector URL                                                                                                                                                                                                                                                                                               |
| InfluxDb      | `"Stickee\\Instrumentation\\Exporters\\Events\\InfluxDb"`      | `INSTRUMENTATION_INFLUXDB_URL="http://localhost:8086"` - The database URL<br>`INSTRUMENTATION_INFLUXDB_TOKEN="my-super-secret-auth-token"` - The authorization token<br>`INSTRUMENTATION_INFLUXDB_BUCKET="test"` - The bucket (database) name<br>`INSTRUMENTATION_INFLUXDB_ORG="stickee"` - The organisation name<br>`INSTRUMENTATION_INFLUXDB_VERIFY_SSL=false` - Verify the SSL certificate |
| LaravelDump   | `"Stickee\\Instrumentation\\Exporters\\Events\\LaravelDump"`   | None                                                                                                                                                                                                                                                                                                                                                                                          |
| LaravelLog    | `"Stickee\\Instrumentation\\Exporters\\Events\\LaravelLog"`    | None                                                                                                                                                                                                                                                                                                                                                                                          |
| Log           | `"Stickee\\Instrumentation\\Exporters\\Events\\Log"`           | `INSTRUMENTATION_LOG_FILE_FILENAME="/path/to/file.log"` - The log file                                                                                                                                                                                                                                                                                                                        |
| NullEvents    | `"Stickee\\Instrumentation\\Exporters\\Events\\NullEvents"`    | None                                                                                                                                                                                                                                                                                                                                                                                          |

The Spans exporter defaults to `NullSpans`. To change this, set `INSTRUMENTATION_SPANS_EXPORTER`

| Class         | `INSTRUMENTATION_SPANS_EXPORTER` Value                        | Other Values                                                                                                                                                                                                                                                                                                                                                                                  |
|---------------|---------------------------------------------------------------|-------------------------------------------------------------------------------------------------|
| OpenTelemetry | `"Stickee\\Instrumentation\\Exporters\\Spans\\OpenTelemetry"` | `INSTRUMENTATION_OPENTELEMETRY_DSN="http://example.com:4318"` - The OpenTelemetry Collector URL |
| NullSpans     | `"Stickee\\Instrumentation\\Exporters\\Spans\\NullSpans"`     | None                                                                                            |

If you wish to, you can copy the package config to your local config with the publish command,
however this is **unnecessary** in normal usage:

```bash
php artisan vendor:publish --provider="Stickee\Instrumentation\Laravel\ServiceProvider"
```

### Setting Up Exporters

#### Using Open Telemetry (Events and / or Spans)

 - Install OpenTelemetry packages: `composer require open-telemetry/exporter-otlp:1.0.0beta-12 open-telemetry/opentelemetry-logger-monolog:^0.0.2 google/protobuf`
 - Publish the OpenTelemetry config: `php artisan vendor:publish --provider="PlunkettScott\LaravelOpenTelemetry\OtelServiceProvider" --tag=otel-config`
 - Recommended - change `OTEL_ENABLED` to `INSTRUMENTATION_ENABLED`
 - Set `INSTRUMENTATION_OPENTELMETRY_DSN`, and one or both of `INSTRUMENTATION_EVENTS_EXPORTER` and `INSTRUMENTATION_SPANS_EXPORTER`

#### Using InfluxDb (Events only)

 - Install the InfluxDB PHP client: `composer require influxdata/influxdb-client-php`
 - Set the .env variables `INSTRUMENTATION_EVENTS_EXPORTER` and `INSTRUMENTATION_INFLUXDB_*`

#### Using Custom Exporters

If you wish to use a custom class to export Events or Spans then you simply need to implement `Stickee\Instrumentation\Exporters\Interfaces\EventsExporterInterface`
or `Stickee\Instrumentation\Exporters\Interfaces\SpansExporterInterface` and make sure it is constructable by the service container.

## Usage

> If you're not using the static accessor/facade, replace `Instrument::` with `$exporter->`

### Events

There are 3 event type methods defined in the `Stickee\Instrumentation\Exporters\Interfaces\EventsExporterInterface` interface.

| Function                                                                  | Arguments                                                                                                 | Description                         |
|---------------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------|-------------------------------------|
| `Instrument::event(string $name, array $tags = [], float value = 1)`      | `$name` The event name<br>`$tags` An array of tags<br>`$value` The value to record                        | Record a single event               |
| `Instrument::count(string $event, array $tags = [], float $increase = 1)` | `$name` The counter name<br>`$tags` An array of tags<br>`$increase` The amount to increase the counter by | Record an increase in a counter     |
| `Instrument::gauge(string $event, array $tags = [], float $value)`        | `$name` The gauge name<br>`$tags` An array of tags<br>`$value` The value to record                        | Record the current value of a gauge |

Tags should be an associative array of `tag_name` => `tag_value`, e.g.

```php
$tags = ['datacentre' => 'uk', 'http_status' => \Symfony\Component\HttpFoundation\Response::HTTP_OK];
```

### Spans

| Function                                                                                                                                             | Arguments                                                                                                                                                                | Description                                                                             |
|------------------------------------------------------------------------------------------------------------------------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------|
| `Instrument::span(string $name, callable $callable, int $kind = \OpenTelemetry\API\Trace\SpanKind::KIND_INTERNAL, iterable $attributes = []): mixed` | `$name` The span name<br>`$callable` A callable that will be executed within the span context<br>`$kind` The kind of span<br>`$attributes` Attributes to add to the span | Exceute a callback inside a span                                                        |
| `Instrument::startSpan(string $name, int $kind = SpanKind::KIND_INTERNAL, iterable $attributes = []): \Stickee\Instrumentation\Spans\SpanInterface`  | `$name` The span name<br>`$kind` The kind of span<br>`$attributes` Attributes to add to the span                                                                         | Start a span. This will return a span object, on which you must call the `end()` method |

Examples:

```php
Instrument::span('my_span', function () {
    // Do something
});

$span = Instrument::startSpan('my_span');
// Do something
$span->end();
```

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
This will start Grafana, Loki, Tempo, InfluxDB, and the OpenTelemetry Collector and expose them on your local machine.

 - Grafana: http://localhost:3000
 - OpenTelemetry Collector: http://localhost:4318 (this should be used for `INSTRUMENTATION_OPENTELEMETRY_DSN`)
 - InfluxDb: http://localhost:8086 (this should be used for `INSTRUMENTATION_INFLUXDB_URL`)

#### InfluxDB

Go to `./vendor/stickee/instrumentation/docker/influxdb` and run `docker compose up`.
This will start Chronograf and InfluxDB and expose them on your local machine.

 - Chronograf: http://localhost:8888
 - InfluxDB: http://localhost:8086 (this should be used for `INSTRUMENTATION_INFLUXDB_URL`)

## Contributions

Contributions are welcome to all areas of the project, but please provide tests. Code style will be checked using
automatically checked via [Stickee Canary](https://github.com/stickeeuk/canary) on your pull request. You can however
install it locally using instructions on the above link.

## License

Instrumentation is open source software licensed under the [MIT license](https://opensource.org/licenses/MIT).
