# Stickee Instrumentation

This a Composer package for recording metrics.
It builds on the [OpenTelemetry PHP Instrumentation](https://opentelemetry.io/docs/languages/php/) and [OpenTelemetry Laravel auto-instrumentation](https://github.com/open-telemetry/opentelemetry-php-contrib/tree/main/src/Instrumentation/Laravel) packages to automatically record performance metrics and provide a simple interface for recording custom metrics.

## Quickstart

### Requirements

This package requires PHP 8.3 or later and Laravel 11 or later.

> The [ext-opentelemetry extension](https://github.com/open-telemetry/opentelemetry-php-instrumentation) and
> [ext-protobuf extension](https://github.com/protocolbuffers/protobuf/tree/main/php) ([Windows download](https://pecl.php.net/package/protobuf)) are required.

### Installation

```bash
composer require stickee/instrumentation
```

### Configuration

The package ships with a default configuration that should be suitable for most use cases.
It is disabled by default; to enable it, set `OTEL_PHP_AUTOLOAD_ENABLED="true"` in your `php.ini` or your environment.
Then set `INSTRUMENTATION_EVENTS_EXPORTER` and `INSTRUMENTATION_SPANS_EXPORTER` to the desired exporter classes and set the required configuration for the exporter.

> :warning: `OTEL_PHP_AUTOLOAD_ENABLED="true"` (and other `OTEL_` variables) will **NOT** work properly if set in your `.env` file, as they are used before the `.env` file is loaded.

> Note: You may need to set variables in multiple `php.ini` files, e.g. `/etc/php/8.3/cli/php.ini` and `/etc/php/8.3/apache2/php.ini` to enable it for both CLI (commands, crons and queues) and web requests.

For more advanced configuration, see the [OpenTelemetry SDK Configuration](https://opentelemetry.io/docs/languages/php/sdk/#configuration).

| Variable                                           | Description                                                      | Default                                                                                                                            |
|----------------------------------------------------|------------------------------------------------------------------|------------------------------------------------------------------------------------------------------------------------------------|
| `INSTRUMENTATION_EVENTS_EXPORTER`                  | The class name of the events exporter to use.                    | `Stickee\Instrumentation\Exporters\Events\OpenTelemetry` on production, else `Stickee\Instrumentation\Exporters\Events\NullEvents` |
| `INSTRUMENTATION_SPANS_EXPORTER`                   | The class name of the spans exporter to use.                     | `Stickee\Instrumentation\Exporters\Spans\OpenTelemetry` on production, else `Stickee\Instrumentation\Exporters\Spans\NullSpans`    |
| `INSTRUMENTATION_LOG_FILE_FILENAME`                | The log file to write to.                                        | `instrumentation.log`                                                                                                              |
| `INSTRUMENTATION_RESPONSE_TIME_MIDDLEWARE_ENABLED` | Enable or disable the response time middleware.                  | `true`                                                                                                                             |
| `INSTRUMENTATION_TRACE_SAMPLE_RATE`                | The rate at which to sample traces.                              | `1.0`                                                                                                                              |
| `INSTRUMENTATION_SCRUBBING_REGEXES`                | Comma-separated regular expressions for scrubbing data.          | `null` (null uses built-in defaults)                                                                                               |
| `INSTRUMENTATION_SCRUBBING_CONFIG_KEY_REGEXES`     | Comma-separated regular expressions for scrubbing config values. | `null` (null uses built-in defaults)                                                                                               |

#### Event Exporters

| Class         | `INSTRUMENTATION_EVENTS_EXPORTER` Value                        | Other Values                                                                                                                                                                                                                                                                                                                                                                                  |
|---------------|----------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| OpenTelemetry | `"Stickee\\Instrumentation\\Exporters\\Events\\OpenTelemetry"` | `INSTRUMENTATION_OPENTELEMETRY_DSN="http://example.com:4318"` - The OpenTelemetry Collector URL                                                                                                                                                                                                                                                                                               |
| LaravelDump   | `"Stickee\\Instrumentation\\Exporters\\Events\\LaravelDump"`   | None                                                                                                                                                                                                                                                                                                                                                                                          |
| LaravelLog    | `"Stickee\\Instrumentation\\Exporters\\Events\\LaravelLog"`    | None                                                                                                                                                                                                                                                                                                                                                                                          |
| LogFile       | `"Stickee\\Instrumentation\\Exporters\\Events\\LogFile"`       | `INSTRUMENTATION_LOG_FILE_FILENAME="/path/to/file.log"` - The log file                                                                                                                                                                                                                                                                                                                        |
| NullEvents    | `"Stickee\\Instrumentation\\Exporters\\Events\\NullEvents"`    | None                                                                                                                                                                                                                                                                                                                                                                                          |

#### Span Exporters

| Class         | `INSTRUMENTATION_SPANS_EXPORTER` Value                        | Other Values                                                                                    |
|---------------|---------------------------------------------------------------|-------------------------------------------------------------------------------------------------|
| OpenTelemetry | `"Stickee\\Instrumentation\\Exporters\\Spans\\OpenTelemetry"` | `INSTRUMENTATION_OPENTELEMETRY_DSN="http://example.com:4318"` - The OpenTelemetry Collector URL |
| NullSpans     | `"Stickee\\Instrumentation\\Exporters\\Spans\\NullSpans"`     | None                                                                                            |

If you wish to, you can copy the package config to your local config with the publish command,
however this is **unnecessary** in normal usage:

```bash
php artisan vendor:publish --provider="Stickee\Instrumentation\Laravel\Providers\InstrumentationServiceProvider"
```

## Usage

Installing the package will automatically record many metrics for you.
If you wish to track custom metrics, you can use the `Instrumentation` facade.

```php
use Stickee\Instrumentation\Facades\Instrumentation;

Instrumentation::event('my_event', ['datacentre' => 'uk', 'http_status' => \Symfony\Component\HttpFoundation\Response::HTTP_OK]);
Instrumentation::counter('my_counter', ['datacentre' => 'uk'], 1);
Instrumentation::gauge('my_gauge', ['datacentre' => 'uk'], 123);
Instrument::histogram('my_histogram', 's', '', [1, 2, 4, 8], ['datacentre' => 'uk'], 123);
```

### Observation Types

There are 4 methods defined in the `Stickee\Instrumentation\Exporters\Interfaces\EventsExporterInterface` interface.

| Event                                                                                                                               | Arguments                                                                                                                                                                                                                                                       | Description                             |
|-------------------------------------------------------------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|-----------------------------------------|
| `$exporter->event(string $name, array $attributes = [], float value = 1)`                                                           | `$name` The event name<br>`$attributes` An array of attributes                                                                                                                                                                                                  | Record a single event                   |
| `$exporter->counter(string $event, array $attributes = [], float $increase = 1)`                                                    | `$name` The counter name<br>`$attributes` An array of attributes<br>`$increase` The amount to increase the counter by                                                                                                                                           | Record an increase in a counter         |
| `$exporter->gauge(string $event, array $attributes = [], float $value)`                                                             | `$name` The gauge name<br>`$attributes` An array of attributes<br>`$value` The value to record                                                                                                                                                                  | Record the current value of a gauge     |
| `$exporter->histogram(string $name, ?string $unit, ?string $description, array $buckets, array $attributes = [], float\|int $value)` | `$name` The histogram name<br>`$unit` The unit of the histogram, e.g. `"ms"`<br>`$description` A description of the histogram<br>`$buckets` A set of buckets, e.g. `[0.25, 0.5, 1, 5]`<br>`$value` The value to record<br>`$attributes` An array of attributes |  Record the current value of a histogram |

Attributes should be an associative array of `attribute_name` ⇒ `attribute_value`, e.g.

```php
$attributes = ['datacentre' => 'uk', 'http_status' => \Symfony\Component\HttpFoundation\Response::HTTP_OK];
```

### Viewing Metrics Locally

There are two ways to view metrics locally: run the OpenTelemetry stack and look at Grafana, or change the exporter to one of the local exporters.

> Note: Spans are only supported by OpenTelemetry.

To choose where the data is exported to, set the `INSTRUMENTATION_EVENTS_EXPORTER` and `INSTRUMENTATION_SPANS_EXPORTER` environment variables..

##### Event Exporters

Event exporters are classes implementing the `Stickee\Instrumentation\Exporters\Interfaces\EventsExporterInterface` interface.
This package ships with the following classes:

| Class         | Description                                                                             |
|---------------|-----------------------------------------------------------------------------------------|
| OpenTelemetry | Writes to an [OpenTelemetry Collector](https://opentelemetry.io/docs/collector/)        |
| LaravelDump   | Uses the [Laravel `dump()`](https://laravel.com/docs/master/helpers#method-dump) helper |
| LaravelLog    | Writes to the [Laravel `Log`](https://laravel.com/docs/master/logging)                  |
| LogFile       | Writes to a log file                                                                    |
| NullEvents    | Discards all data                                                                       |

**Note:** Only `OpenTelemetry` is recommended for production use.
The others are for development / debugging.
See the Configuration section for more information.

##### Span Exporters

Span exporters are classes implementing the `Stickee\Instrumentation\Exporters\Interfaces\SpansExporterInterface` interface.
This package ships with the following classes:

| Class         | Description                                                                      |
|---------------|----------------------------------------------------------------------------------|
| OpenTelemetry | Writes to an [OpenTelemetry Collector](https://opentelemetry.io/docs/collector/) |
| NullSpans     | Discards all data                                                                |

#### OpenTelemetry

Go to `./vendor/stickee/instrumentation/docker/opentelemetry` and run `docker compose up`.
This will start Grafana, Loki, Tempo, Prometheus, and the OpenTelemetry Collector and expose them on your local machine.

 - Grafana: http://localhost:3000
 - OpenTelemetry Collector: http://localhost:4318 (this should be used for `INSTRUMENTATION_OPENTELEMETRY_DSN`)

### Using a Custom Exporter

If you wish to use a custom exporter class for `INSTRUMENTATION_EVENTS_EXPORTER` then you simply need to implement `Stickee\Instrumentation\Exporters\Interfaces\EventsExporterInterface` and make sure it is constructable by the service container.

### Scrubbing Data

By default, data is scrubbed using regexes from `INSTRUMENTATION_SCRUBBING_REGEXES` and values from the config where the keys match `INSTRUMENTATION_SCRUBBING_CONFIG_KEY_REGEXES`.
To use a custom scrubber, bind your implementation to the `Stickee\Instrumentation\DataScrubbers\DataScrubberInterface` interface.
The package ships with `NullDataScrubber` to disable scrubbing, `CallbackDataScrubber` to allow you to register a callback instead of creating a new class, and `MultiDataScrubber` to bind multiple scrubbers.

```php
use Stickee\Instrumentation\DataScrubbers\DataScrubberInterface;
use Stickee\Instrumentation\DataScrubbers\NullDataScrubber;
use Stickee\Instrumentation\DataScrubbers\CallbackDataScrubber;

// Disable scrubbing
app()->bind(DataScrubberInterface::class, NullDataScrubber::class);

// Custom scrubbing
app()->bind(DataScrubberInterface::class, fn () => new CallbackDataScrubber(fn (mixed $key, mixed $value) => preg_replace('/\d/', 'x', $value)));
```

## Developing

The easiest way to make changes is to make the project you're importing the package in to load the package from your filesystem instead of the Composer repository, like this:

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

## OpenTelemetry Collector

The Instrumentation package uses the OpenTelemetry Collector to export metrics.
Data is sent from PHP using HTTP + Protobuf to the OpenTelemetry Collector, which is usually running on localhost or as a sidecar in Kubernetes.


Due to PHP's shared-nothing architecture, we need to send Delta temporality metrics to the OpenTelemetry Collector, otherwise every
PHP process (and every hit to a website) would need a unique data stream, which would not perform adequately.
To get around this, we use Delta temporality and the OpenTelemetry Collector's Aggregation and DeltaToCumulative processors to aggregate metrics in memory before sending them to the exporter.
Each collector is given a unique `service.instance.id` to allow them to be aggregated together later.

The Aggregation processor is written by Stickee and is available in the [Stickee OpenTelemetry Collector](https://github.com/stickeeuk/opentelemetry-collector-contrib)
repository on the `feature/aggregation-processor` branch.

To update it, run the following commands based off the [Custom Collector documentation](https://opentelemetry.io/docs/collector/custom-collector/):

```bash
git clone git@github.com:stickeeuk/opentelemetry-collector-contrib.git
cd opentelemetry-collector-contrib
docker run --rm -it -v /$(pwd)://go/src golang:1.22-bookworm # Run a Go docker container

# Inside the container
apt update && apt install -y vim
mkdir /builder
cd /builder
curl --proto '=https' --tlsv1.2 -fL -o ocb https://github.com/open-telemetry/opentelemetry-collector-releases/releases/download/cmd%2Fbuilder%2Fv0.113.0/ocb_0.113.0_linux_amd64
chmod +x ocb

# Paste in the contents of the `cmd/otelcontribcol/builder-config.yaml` file from the repository
# but replace `../..` with `/go/src` and add `output_path: ./output` to the `dist:` section.
vi builder-config.yaml

./ocb --config builder-config.yaml
cp ./output/otelcontribcol /go/src/bin/otelcontribcol_linux_amd64_stickee

# Exit the container
exit

# Back on the host

# Copy the binary to the repository
cp bin/otelcontribcol_linux_amd64_stickee ../instrumentation/docker/opentelemetry-collector/contrib

cd ../instrumentation/docker/opentelemetry-collector/contrib

# Build and push the Docker image
docker build -t ghcr.io/stickeeuk/opentelemetry-collector .
docker push ghcr.io/stickeeuk/opentelemetry-collector
```

## Contributions

Contributions are welcome to all areas of the project, but please provide tests. Code style will be checked using
automatically checked via [Stickee Canary](https://github.com/stickeeuk/canary) on your pull request. You can however
install it locally using instructions on the above link.

## License

Instrumentation is open source software licensed under the [MIT license](https://opensource.org/licenses/MIT).
