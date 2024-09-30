<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Queue;

it('it gets the available queue size', function (): void {
    expect(Queue::availableSize())->toBe(0);
});
