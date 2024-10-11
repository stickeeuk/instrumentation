<?php

declare(strict_types=1);

use Stickee\Instrumentation\Exporters\Events\NullEvents;

beforeEach(function (): void {
    $this->exporter = new NullEvents();
});

it('does nothing when receiving an event', function (): void {
    try {
        $this->exporter->event(
            $this->faker()->sentence(),
        );

        expect(true)->toBeTrue();
    } catch (Throwable $throwable) {
        $this::fail("Failed to do nothing: {$throwable->getMessage()}");
    }
});

it('does nothing when receiving a counter', function (): void {
    try {
        $this->exporter->counter(
            $this->faker()->sentence(),
            [],
            $this->faker()->randomFloat(2),
        );

        expect(true)->toBeTrue();
    } catch (Throwable $throwable) {
        $this::fail("Failed to do nothing: {$throwable->getMessage()}");
    }
});

it('does nothing when receiving a gauge', function (): void {
    try {
        $this->exporter->gauge(
            $this->faker()->sentence(),
            [],
            $this->faker()->randomFloat(2),
        );

        expect(true)->toBeTrue();
    } catch (Throwable $throwable) {
        $this::fail("Failed to do nothing: {$throwable->getMessage()}");
    }
});

it('does nothing when receiving a flush', function (): void {
    try {
        $this->exporter->flush();

        expect(true)->toBeTrue();
    } catch (Throwable $throwable) {
        $this::fail("Failed to do nothing: {$throwable->getMessage()}");
    }
});
