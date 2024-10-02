<?php

declare(strict_types=1);

namespace Stickee\Instrumentation\Utils;

use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\SDK\Common\Future\CancellationInterface;
use OpenTelemetry\SDK\Trace\ReadableSpanInterface;
use OpenTelemetry\SDK\Trace\ReadWriteSpanInterface;
use OpenTelemetry\SDK\Trace\SpanProcessorInterface;
use Stickee\Instrumentation\DataScrubbers\DataScrubberInterface;

class DataScrubbingSpanProcessor implements SpanProcessorInterface
{
    public function __construct(private readonly DataScrubberInterface $scrubber)
    {
    }

    public function onStart(ReadWriteSpanInterface $span, ContextInterface $parentContext): void
    {
        $attributes = $span->toSpanData()->getAttributes();

        foreach ($attributes as $key => $value) {
            $scrubbed = $this->scrubber->scrub($key, $value);

            if ($scrubbed !== $value) {
                $span->setAttribute($key, $scrubbed);
            }
        }
    }

    public function onEnd(ReadableSpanInterface $span): void
    {
        // Do nothing.
    }

    public function forceFlush(?CancellationInterface $cancellation = null): bool
    {
        return true;
    }

    public function shutdown(?CancellationInterface $cancellation = null): bool
    {
        return true;
    }
}
