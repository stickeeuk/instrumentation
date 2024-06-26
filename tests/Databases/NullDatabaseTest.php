<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use Stickee\Instrumentation\Exporters\Events\NullEvents;

beforeEach(function (): void {
    $this->database = new NullEvents();
});

it('does nothing when receiving an event', function (): void {
    try {
        $this->database->event(
            $this->faker()->sentence(),
        );

        expect(true)->toBeTrue();
    } catch (Throwable $throwable) {
        $this::fail("Failed to do nothing: {$throwable->getMessage()}");
    }
});

it('does nothing when receiving a count', function (): void {
    try {
        $this->database->count(
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
        $this->database->gauge(
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
       $this->database->flush();

       expect(true)->toBeTrue();
   } catch (Throwable $throwable) {
       $this::fail("Failed to do nothing: {$throwable->getMessage()}");
   }
});
