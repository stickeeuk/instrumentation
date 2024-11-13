<?php

namespace Stickee\Instrumentation\Laravel;

use Exception;

/**
 * Config
 */
class Config
{
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
     * If the response time middleware is enabled
     */
    public function responseTimeMiddlewareEnabled(): bool
    {
        return (bool) config('instrumentation.response_time_middleware_enabled', true);
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
