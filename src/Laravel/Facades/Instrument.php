<?php

namespace Stickee\Instrumentation\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Instrumentation Laravel facade
 *
 * @method static void setErrorHandler($errorHandler)
 * @method static void event(string $name, array $tags = [], float $value = 1)
 * @method static void counter(string $name, array $tags = [], float $increase = 1)
 * @method static void gauge(string $name, array $tags, float $value)
 * @method static void histogram(string $name, ?string $unit, ?string $description, array $buckets, float|int $value, array $tags = [])
 * @method static void flush()
 * @method static mixed span(string $name, callable $callable, int $kind = SpanKind::KIND_INTERNAL, iterable $attributes = [])
 * @method static SpanInterface startSpan(string $name, int $kind = SpanKind::KIND_INTERNAL, iterable $attributes = [])
 *
 * @see \Stickee\Instrumentation\Exporters\Exporter
 */
class Instrument extends Facade
{
    /**
     * Get the facade accessor
     */
    protected static function getFacadeAccessor()
    {
        // Bound to \Stickee\Instrumentation\Exporters\Exporter
        return 'instrument';
    }
}
