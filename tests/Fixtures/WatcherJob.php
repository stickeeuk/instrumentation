<?php

declare(strict_types=1);

namespace Stickee\Instrumentation\Tests\Fixtures;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class WatcherJob implements ShouldQueue
{
    use Queueable;

    public function __construct(private int $memoryToUse = 0) {}

    public function handle(): void
    {
        DB::select('SELECT 1');
        DB::select('SELECT 1');

        str_repeat(' ', $this->memoryToUse * 1024 * 1024);
    }
}
