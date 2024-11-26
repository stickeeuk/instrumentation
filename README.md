# Stickee Instrumentation

This a Composer package for recording metrics.
It builds on the [OpenTelemetry PHP Instrumentation](https://opentelemetry.io/docs/languages/php/)
and [OpenTelemetry Laravel auto-instrumentation](https://github.com/open-telemetry/opentelemetry-php-contrib/tree/main/src/Instrumentation/Laravel)
packages to automatically record performance metrics and provide a simple interface for recording custom metrics.

## Quickstart

### Requirements

This package requires PHP 8.3 or later and Laravel 11 or later.

> The [ext-opentelemetry extension](https://github.com/open-telemetry/opentelemetry-php-instrumentation) and
> [ext-protobuf extension](https://github.com/protocolbuffers/protobuf/tree/main/php)
> ([Windows download](https://pecl.php.net/package/protobuf)) are required.

**NB**: For those running macOS, you should ensure that your PHP installation is managed by Homebrew (`brew install php`)
as the default PHP installation on macOS is almost always insufficient.

For those using [Laravel Herd](https://herd.laravel.com) as
a means to manage local PHP installations, you will need to consult the
[relevant documentation](https://herd.laravel.com/docs/1/technology/php-extensions#installing-php-extensions) to ensure
your local PHP installation (outside of Docker containers) is correctly configured and has access to the required
extensions.

### Installation

```bash
composer require stickee/instrumentation
```

### Configuration

The package ships with a default configuration that should be suitable for most use cases except you should add
`OTEL_EXPORTER_OTLP_METRICS_TEMPORALITY_PREFERENCE="delta"`.
It is disabled by default; to enable it, set `OTEL_PHP_AUTOLOAD_ENABLED="true"` in your `php.ini` or your environment.

> :warning: `OTEL_PHP_AUTOLOAD_ENABLED="true"` (and other `OTEL_` variables) will **NOT** work properly if set
> in your `.env` file, as they are used before the `.env` file is loaded.

> Note: You may need to set variables in multiple `php.ini` files, e.g. `/etc/php/8.3/cli/php.ini` and
> `/etc/php/8.3/apache2/php.ini` to enable it for both CLI (commands, crons and queues) and web requests.

For more advanced configuration, see the [OpenTelemetry SDK Configuration](https://opentelemetry.io/docs/languages/php/sdk/#configuration).

| Variable                                           | Description                                                      | Default                                                                                                                            |
|----------------------------------------------------|------------------------------------------------------------------|------------------------------------------------------------------------------------------------------------------------------------|
| `INSTRUMENTATION_RESPONSE_TIME_MIDDLEWARE_ENABLED` | Enable or disable the response time middleware.                  | `true`                                                                                                                             |
| `INSTRUMENTATION_TRACE_SAMPLE_RATE`                | The rate at which to sample traces.                              | `1.0`                                                                                                                              |
| `INSTRUMENTATION_SCRUBBING_REGEXES`                | Comma-separated regular expressions for scrubbing data.          | `null` (null uses built-in defaults)                                                                                               |
| `INSTRUMENTATION_SCRUBBING_CONFIG_KEY_REGEXES`     | Comma-separated regular expressions for scrubbing config values. | `null` (null uses built-in defaults)                                                                                               |

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

> :warning: Every combination of attributes will be recorded as a separate metric, so be careful not to record too many attributes.

### Viewing Metrics Locally

Run the OpenTelemetry stack and view Grafana at http://localhost:3000.

Go to `./vendor/stickee/instrumentation/docker/opentelemetry` and run `docker compose up -d`.
This will start Grafana, Loki, Tempo, Prometheus, and the OpenTelemetry Collector and expose them on your local machine.

By default the package will send data to the OpenTelemetry Collector on http://localhost:4318.
If you need to change this, set the `OTEL_EXPORTER_OTLP_ENDPOINT` environment variable.
For example if your PHP is running in a docker container you can set `OTEL_EXPORTER_OTLP_ENDPOINT="http://host.docker.internal:4318"`.

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

# Paste in the contents of `docker/opentelemetry-collector-contrib/builder-config.yaml`
vi builder-config.yaml

./ocb --config builder-config.yaml
cp ./output/otelcontribcol /go/src/bin/otelcol-contrib

# Exit the container
exit

# Back on the host

# Copy the binary to the repository
cp bin/otelcol-contrib ../instrumentation/docker/opentelemetry-collector-contrib

cd ../instrumentation/docker/opentelemetry-collector-contrib

# Build, test and push the Docker image
docker build -t ghcr.io/stickeeuk/opentelemetry-collector .
docker run --rm ghcr.io/stickeeuk/opentelemetry-collector
docker push ghcr.io/stickeeuk/opentelemetry-collector

# Tar the binary
tar -czvf otelcol-contrib-0.114.0.tar.gz otelcol-contrib

# Upload the .tar.gz file to the Projects (80.85.84.15) server as
# /srv/users/repo/apps/repo/public/otelcol-contrib-0.114.0.tar.gz
```

## Contributions

Contributions are welcome to all areas of the project, but please provide tests. Code style will be checked using
automatically checked via [Stickee Canary](https://github.com/stickeeuk/canary) on your pull request. You can however
install it locally using instructions on the above link.

## License

Instrumentation is open source software licensed under the [MIT license](https://opensource.org/licenses/MIT).
