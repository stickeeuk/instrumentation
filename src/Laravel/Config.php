<?php

namespace Stickee\Instrumentation\Laravel;

use Exception;
use Stickee\Instrumentation\Exporters\Events\NullEvents;
use Stickee\Instrumentation\Exporters\Interfaces\EventsExporterInterface;
use Stickee\Instrumentation\Exporters\Interfaces\SpansExporterInterface;
use Stickee\Instrumentation\Exporters\Spans\NullSpans;

/**
 * Config
 */
class Config
{
    /**
     * If instrumentation is enabled
     *
     * @return bool
     */
    public function enabled(): bool
    {
        return (bool) config('instrumentation.enabled');
    }

    /**
     * Get the events exporter class
     *
     * @return string
     */
    public function eventsExporterClass(): string
    {
        $class = $this->enabled()
            ? config('instrumentation.events_exporter')
            : NullEvents::class;

        if (empty($class)) {
            throw new Exception('Config variable `instrumentation.events_exporter` not set');
        }

        if (! class_exists($class, true)) {
            throw new Exception('Config variable `instrumentation.events_exporter` class not found: ' . $class);
        }

        if (! is_a($class, EventsExporterInterface::class, true)) {
            throw new Exception('Config variable `instrumentation.events_exporter` does not implement \Stickee\Instrumentation\Exporters\Interfaces\EventsExporterInterface: ' . $class);
        }

        return $class;
    }

    /**
     * Get the spans exporter class
     *
     * @return string
     */
    public function spansExporterClass(): string
    {
        $class = $this->enabled()
            ? config('instrumentation.spans_exporter')
            : NullSpans::class;

        if (empty($class)) {
            throw new Exception('Config variable `instrumentation.spans_exporter` not set');
        }

        if (! class_exists($class, true)) {
            throw new Exception('Config variable `instrumentation.spans_exporter` class not found: ' . $class);
        }

        if (! is_a($class, SpansExporterInterface::class, true)) {
            throw new Exception('Config variable `instrumentation.spans_exporter` does not implement \Stickee\Instrumentation\Exporters\Interfaces\SpansExporterInterface: ' . $class);
        }

        return $class;
    }

    /**
     * Get the trace sample rate, between 0 and 1
     *
     * @return float
     */
    public function traceSampleRate(): float
    {
        $value  = (float) config('instrumentation.trace_sample_rate', 0);

        if (($value < 0) || ($value > 1)) {
            throw new Exception('Config variable `instrumentation.trace_sample_rate` must be between 0 and 1');
        }

        return $value;
    }

    /**
     * If SSL connections should verify the certificate
     *
     * @return bool
     */
    public function verifySsl(): bool
    {
        return (bool) config('instrumentation.verify_ssl', true);
    }

    /**
     * If the response time middleware is enabled
     *
     * @return bool
     */
    public function responseTimeMiddlewareEnabled(): bool
    {
        return (bool) config('instrumentation.response_time_middleware_enabled', true);
    }

    /**
     * Configuration for InfluxDb
     *
     * @param string $key The configuration variable
     *
     * @return mixed
     */
    public function influxDb(string $key): mixed
    {
        $value = config('instrumentation.influxdb.' . $key, null);

        if (($value === null) || ($value === '')) {
            throw new Exception('Config variable `instrumentation.influxdb.' . $key . '` not set');
        }

        return $value;
    }

    /**
     * Configuration for OpenTelemetry
     *
     * @param string $key The configuration variable
     *
     * @return mixed
     */
    public function openTelemetry(string $key): mixed
    {
        $value = config('instrumentation.opentelemetry.' . $key, null);

        if (($value === null) || ($value === '')) {
            throw new Exception('Config variable `instrumentation.opentelemetry.' . $key . '` not set');
        }

        return $value;
    }

    /**
     * Configuration for the log file
     *
     * @param string $key The configuration variable
     *
     * @return mixed
     */
    public function logFile(string $key): mixed
    {
        $value = config('instrumentation.log_file.' . $key, null);

        if (($value === null) || ($value === '')) {
            throw new Exception('Config variable `instrumentation.log_file.' . $key . '` not set');
        }

        return $value;
    }

    /**
     * Get the queue names
     *
     * @return array
     */
    public function queueNames(): array
    {
        return config('instrumentation.queue_names', []);
    }

    /**
     * Get the long request trace threshold
     *
     * @return float
     */
    public function longRequestTraceThreshold(): float
    {
        return (float) config('instrumentation.long_request_trace_threshold', 1);
    }
}
