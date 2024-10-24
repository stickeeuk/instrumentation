<?php

declare(strict_types=1);

namespace Stickee\Instrumentation\Utils;

use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Logs\EventLoggerInterface;
use OpenTelemetry\API\Logs\EventLoggerProviderInterface;
use OpenTelemetry\API\Logs\LoggerInterface;
use OpenTelemetry\API\Logs\LoggerProviderInterface;
use OpenTelemetry\API\Metrics\MeterInterface;
use OpenTelemetry\API\Metrics\MeterProviderInterface;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\API\Trace\TracerProviderInterface;
use WeakMap;

final class CachedInstruments
{
    /** @var WeakMap<TracerProviderInterface, TracerInterface> */
    private WeakMap $tracers;

    /** @var WeakMap<MeterProviderInterface, MeterInterface> */
    private WeakMap $meters;

    /** @var WeakMap<LoggerProviderInterface, LoggerInterface> */
    private WeakMap $loggers;

    /** @var WeakMap<EventLoggerProviderInterface, EventLoggerInterface> */
    private WeakMap $eventLoggers;

    /**
     * @psalm-suppress PropertyTypeCoercion
     */
    public function __construct(
        private readonly string $name,
        private readonly ?string $version = null,
        private readonly ?string $schemaUrl = null,
        private readonly iterable $attributes = [],
    ) {
        $this->tracers = new WeakMap();
        $this->meters = new WeakMap();
        $this->loggers = new WeakMap();
        $this->eventLoggers = new WeakMap();
    }

    public function tracer(): TracerInterface
    {
        $tracerProvider = Globals::tracerProvider();

        return $this->tracers[$tracerProvider] ??= $tracerProvider->getTracer($this->name, $this->version, $this->schemaUrl, $this->attributes);
    }

    public function meter(): MeterInterface
    {
        $meterProvider = Globals::meterProvider();

        return $this->meters[$meterProvider] ??= $meterProvider->getMeter($this->name, $this->version, $this->schemaUrl, $this->attributes);
    }

    public function logger(): LoggerInterface
    {
        $loggerProvider = Globals::loggerProvider();

        return $this->loggers[$loggerProvider] ??= $loggerProvider->getLogger($this->name, $this->version, $this->schemaUrl, $this->attributes);
    }

    public function eventLogger(): EventLoggerInterface
    {
        $eventLoggerProvider = Globals::eventLoggerProvider();

        return $this->eventLoggers[$eventLoggerProvider] ??= $eventLoggerProvider->getEventLogger($this->name, $this->version, $this->schemaUrl, $this->attributes);
    }

    public function flush(): void
    {
        $maps = [
            $this->tracers,
            $this->meters,
            $this->loggers,
            $this->eventLoggers,
        ];

        foreach ($maps as $map) {
            foreach ($map as $provider => $instrument) {
                if (method_exists($provider, 'forceFlush')) {
                    $provider->forceFlush();
                }
            }
        }
    }
}
