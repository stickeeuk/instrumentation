<?php

namespace Stickee\Instrumentation\Utils;

use OpenTelemetry\API\Logs\EventLoggerInterface;
use OpenTelemetry\API\Metrics\MeterInterface;
use OpenTelemetry\SDK\Logs\LoggerProviderInterface;
use OpenTelemetry\SDK\Metrics\MeterProviderInterface;

class OpenTelemetryConfig
{
    public MeterProviderInterface $meterProvider;
    public MeterInterface $meter;

    public LoggerProviderInterface $loggerProvider;
    public EventLoggerInterface $eventLogger;

    public function __construct(
        MeterProviderInterface $meterProvider,
        MeterInterface $meter,
        LoggerProviderInterface $loggerProvider,
        EventLoggerInterface $eventLogger
    ) {
        $this->meterProvider = $meterProvider;
        $this->meter = $meter;
        $this->loggerProvider = $loggerProvider;
        $this->eventLogger = $eventLogger;
    }
}
