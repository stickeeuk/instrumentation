<?php

namespace Stickee\Instrumentation\Utils;

use OpenTelemetry\API\Logs\EventLoggerInterface;
use OpenTelemetry\API\Metrics\MeterInterface;
use OpenTelemetry\SDK\Logs\LoggerProviderInterface;
use OpenTelemetry\SDK\Metrics\MeterProviderInterface;

class OpenTelemetryConfig
{
    /**
     * The meter provider
     *
     * @var \OpenTelemetry\SDK\Metrics\MeterProviderInterface $meterProvider
     */
    public MeterProviderInterface $meterProvider;

    /**
     * The meter
     *
     * @var \OpenTelemetry\API\Metrics\MeterInterface $meter
     */
    public MeterInterface $meter;

    /**
     * The logger provider
     *
     * @var \OpenTelemetry\SDK\Logs\LoggerProviderInterface $loggerProvider
     */
    public LoggerProviderInterface $loggerProvider;

    /**
     * The event logger
     *
     * @var \OpenTelemetry\API\Logs\EventLoggerInterface $eventLogger
     */
    public EventLoggerInterface $eventLogger;

    /**
     * Constructor
     *
     * @param \OpenTelemetry\SDK\Metrics\MeterProviderInterface $meterProvider The meter provider
     * @param \OpenTelemetry\API\Metrics\MeterInterface $meter The meter
     * @param \OpenTelemetry\SDK\Logs\LoggerProviderInterface $loggerProvider The logger provider
     * @param \OpenTelemetry\API\Logs\EventLoggerInterface $eventLogger The event logger
     */
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
