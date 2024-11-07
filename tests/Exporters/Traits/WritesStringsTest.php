<?php

declare(strict_types=1);

use Stickee\Instrumentation\Exporters\Events\Traits\WritesStrings;

beforeEach(function (): void {
    $this->exporter = new class {
        use WritesStrings;

        /** @inheritDoc */
        protected function write(string $message): void
        {
            // Do nothing - needed due to abstract status.
        }
    };
});

it('can call the flush, which in turn does nothing for exporters that write strings', function (): void {
    $this->exporter->flush();

    expect(true)->toBeTrue();
});
