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
     */
    public function enabled(): bool
    {
        return (bool) config('instrumentation.enabled');
    }

    /**
     * Get the events exporter class
     */
    public function eventsExporterClass(): string
    {
        $class = $this->enabled()
            ? (string) config('instrumentation.events_exporter')
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
     */
    public function spansExporterClass(): string
    {
        $class = $this->enabled()
            ? (string) config('instrumentation.spans_exporter')
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
     */
    public function verifySsl(): bool
    {
        return (bool) config('instrumentation.verify_ssl', true);
    }

    /**
     * If the response time middleware is enabled
     */
    public function responseTimeMiddlewareEnabled(): bool
    {
        return (bool) config('instrumentation.response_time_middleware_enabled', true);
    }

    /**
     * Configuration for the log file
     *
     * @param string $key The configuration variable
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
     */
    public function queueNames(): array
    {
        return config('instrumentation.queue_names', []);
    }

    /**
     * Get the long request trace threshold
     */
    public function longRequestTraceThreshold(): float
    {
        return (float) config('instrumentation.long_request_trace_threshold', 1);
    }
}
