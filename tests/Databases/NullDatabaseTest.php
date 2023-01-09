<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use Stickee\Instrumentation\Databases\NullDatabase;

beforeEach(function (): void {
    $this->database = new NullDatabase();
});

it('does nothing when an error handler is set', function (): void {
    try {
        // Valid error handler:
        $this->database->setErrorHandler(static function (string $message): void {
            Log::error($message);
        });

        // Invalid error handler:
        $this->database->setErrorHandler(null);

        expect(true)->toBeTrue();
    } catch (Throwable $throwable) {
        $this::fail("Failed to do nothing: {$throwable->getMessage()}");
    }
});

it('does nothing when receiving an event', function (): void {
    try {
        $this->database->event(
            fake()->sentence(),
        );

        expect(true)->toBeTrue();
    } catch (Throwable $throwable) {
        $this::fail("Failed to do nothing: {$throwable->getMessage()}");
    }
});

it('does nothing when receiving a count', function (): void {
    try {
        $this->database->count(
            fake()->sentence(),
            [],
            fake()->randomFloat(2),
        );

        expect(true)->toBeTrue();
    } catch (Throwable $throwable) {
        $this::fail("Failed to do nothing: {$throwable->getMessage()}");
    }
});

it('does nothing when receiving a gauge', function (): void {
    try {
        $this->database->gauge(
            fake()->sentence(),
            [],
            fake()->randomFloat(2),
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

