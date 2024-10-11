<?php

declare(strict_types=1);

namespace Stickee\Instrumentation\Tests\Fixtures;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class WatcherCommand extends Command
{
    public $signature = 'watcher:command';

    public function handle(): int
    {
        DB::select('SELECT 1');
        DB::select('SELECT 1');

        return self::SUCCESS;
    }
}
