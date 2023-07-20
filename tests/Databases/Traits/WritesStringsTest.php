<?php

declare(strict_types=1);

use Stickee\Instrumentation\Databases\Traits\WritesStrings;

beforeEach(function (): void {
    $this->database = new class () {
        use WritesStrings;

        /** @inheritDoc */
        protected function write(string $message): void
        {
            // Do nothing - needed due to abstract status.
        }
    };
});

it('can call the flush, which in turn does nothing for databases that write strings', function (): void {
    try {
        $this->database->flush();
    } catch (Throwable $throwable) {
        $this::fail('Failed to do nothing!');
    }

    expect(true)->toBeTrue();
});

