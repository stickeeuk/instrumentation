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
    /**
     * Constructor
     *
     * @param \Stickee\Instrumentation\DataScrubbers\DataScrubberInterface $scrubber The data scrubber
     */
    public function __construct(private readonly DataScrubberInterface $scrubber) {}

    /**
     * Start a span
     *
     * @param \OpenTelemetry\SDK\Trace\ReadWriteSpanInterface $span The span
     * @param \OpenTelemetry\Context\ContextInterface $parentContext The parent context
     */
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

    /**
     * End a span
     *
     * @param \OpenTelemetry\SDK\Trace\ReadableSpanInterface $span The span
     */
    public function onEnd(ReadableSpanInterface $span): void
    {
        // Do nothing.
    }

    /**
     * Force flush
     *
     * @param \OpenTelemetry\SDK\Common\Future\CancellationInterface|null $cancellation The cancellation token
     */
    public function forceFlush(?CancellationInterface $cancellation = null): bool
    {
        return true;
    }

    /**
     * Shutdown
     *
     * @param \OpenTelemetry\SDK\Common\Future\CancellationInterface|null $cancellation The cancellation token
     */
    public function shutdown(?CancellationInterface $cancellation = null): bool
    {
        return true;
    }
}
