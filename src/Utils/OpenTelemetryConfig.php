<?php

namespace Stickee\Instrumentation\Utils;

use OpenTelemetry\API\Logs\EventLoggerInterface;
use OpenTelemetry\API\Metrics\MeterInterface;
use OpenTelemetry\SDK\Logs\LoggerProviderInterface;
use OpenTelemetry\SDK\Metrics\MeterProviderInterface;

class OpenTelemetryConfig
{
    /**
     * Constructor
     *
     * @param \OpenTelemetry\SDK\Metrics\MeterProviderInterface $meterProvider The meter provider
     * @param \OpenTelemetry\API\Metrics\MeterInterface $meter The meter
     * @param \OpenTelemetry\SDK\Logs\LoggerProviderInterface $loggerProvider The logger provider
     * @param \OpenTelemetry\API\Logs\EventLoggerInterface $eventLogger The event logger
     */
    public function __construct(
        public readonly MeterProviderInterface $meterProvider,
        public readonly MeterInterface $meter,
        public readonly LoggerProviderInterface $loggerProvider,
        public readonly EventLoggerInterface $eventLogger
    ) {
    }
}
