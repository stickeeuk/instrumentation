<?php

declare(strict_types=1);

namespace Stickee\Instrumentation\Utils;

use Closure;
use OpenTelemetry\API\Behavior\LogsMessagesTrait;
use OpenTelemetry\API\Common\Time\ClockInterface;
use OpenTelemetry\Context\Context;
use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\SDK\Common\Future\CancellationInterface;
use OpenTelemetry\SDK\Trace\ReadableSpanInterface;
use OpenTelemetry\SDK\Trace\ReadWriteSpanInterface;
use OpenTelemetry\SDK\Trace\SpanExporterInterface;
use OpenTelemetry\SDK\Trace\SpanProcessorInterface;
use SplQueue;
use Throwable;

use function sprintf;

/**
 * This is a copy of OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor with the addition of a time threshold
 */
class SlowSpanProcessor implements SpanProcessorInterface
{
    use LogsMessagesTrait;

    private ContextInterface $exportContext;

    private bool $running = false;

    /** @var SplQueue<array{Closure, string, bool, ContextInterface}> */
    private SplQueue $queue;

    /** @var SplQueue<array{Closure, string, bool, ContextInterface}> */
    private SplQueue $deferredQueue;

    private bool $closed = false;

    private readonly int $start;

    public function __construct(
        private readonly SpanExporterInterface $exporter,
        private readonly ClockInterface $clock,
        private readonly float $timeThreshold = 2
    ) {
        $this->exportContext = Context::getCurrent();
        $this->queue = new SplQueue();
        $this->deferredQueue = new SplQueue();
        $this->start = $this->clock->now();
    }

    public function onStart(ReadWriteSpanInterface $span, ContextInterface $parentContext): void {}

    public function onEnd(ReadableSpanInterface $span): void
    {
        if ($this->closed) {
            return;
        }

        // This is the opposite to normal behaviour - we only want to export spans that are not sampled
        if ($span->getContext()->isSampled()) {
            return;
        }

        $spanData = $span->toSpanData();

        if ($this->clock->now() - $this->start >= $this->timeThreshold * ClockInterface::NANOS_PER_SECOND) {
            $this->deferredQueue->enqueue([fn() => $this->exporter->export([$spanData])->await(), 'export', false, $this->exportContext]);
        } else {
            while (! $this->deferredQueue->isEmpty()) {
                call_user_func_array([$this, 'flush'], $this->deferredQueue->dequeue());
            }

            $this->flush(fn() => $this->exporter->export([$spanData])->await(), 'export', false, $this->exportContext);
        }
    }

    public function forceFlush(?CancellationInterface $cancellation = null): bool
    {
        if ($this->closed) {
            return false;
        }

        return $this->flush(fn(): bool => $this->exporter->forceFlush($cancellation), __FUNCTION__, true, Context::getCurrent());
    }

    public function shutdown(?CancellationInterface $cancellation = null): bool
    {
        if ($this->closed) {
            return false;
        }

        $this->closed = true;

        return $this->flush(fn(): bool => $this->exporter->shutdown($cancellation), __FUNCTION__, true, Context::getCurrent());
    }

    private function flush(Closure $task, string $taskName, bool $propagateResult, ContextInterface $context): bool
    {
        $this->queue->enqueue([$task, $taskName, $propagateResult && ! $this->running, $context]);

        if ($this->running) {
            return false;
        }

        $success = true;
        $exception = null;
        $this->running = true;

        try {
            while (! $this->queue->isEmpty()) {
                [$task, $taskName, $propagateResult, $context] = $this->queue->dequeue();
                $scope = $context->activate();

                try {
                    $result = $task();

                    if ($propagateResult) {
                        $success = $result;
                    }
                } catch (Throwable $e) {
                    if ($propagateResult) {
                        $exception = $e;
                    } else {
                        self::logError(sprintf('Unhandled %s error', $taskName), ['exception' => $e]);
                    }
                } finally {
                    $scope->detach();
                }
            }
        } finally {
            $this->running = false;
        }

        if ($exception instanceof \Throwable) {
            throw $exception;
        }

        return $success;
    }
}
