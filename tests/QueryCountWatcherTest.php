<?php

declare(strict_types=1);

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schedule;
use Stickee\Instrumentation\Laravel\Facades\Instrument;
use Stickee\Instrumentation\Tests\Fixtures\WatcherCommand;
use Stickee\Instrumentation\Tests\Fixtures\WatcherJob;
use Stickee\Instrumentation\Utils\SemConv;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;

it('can count queries in jobs', function (): void {
    $pass = false;

    Instrument::partialMock()
        ->allows('counter') // TODO why do we need to ignore the counter registered in ISP?
        ->shouldReceive('histogram')
        ->atLeast()
        ->once()
        ->andReturnUsing(function (string $name, float|int $value, ?string $unit, ?string $description, array $buckets, array $attributes = []) use (&$pass) {
            if ($name === SemConv::DB_QUERIES_NAME) {
                $pass = ($value === 2);
            }
        });

    Instrument::shouldReceive('flush');

    DB::select('SELECT 1');

    // Dispatch the job twice to make sure it's just counting the queries in the job
    WatcherJob::dispatch();
    WatcherJob::dispatch();

    DB::select('SELECT 1');

    $this->assertTrue($pass);
});

it('can count queries in schedules', function (): void {
    $pass = false;

    Instrument::shouldReceive('histogram')
        ->atLeast()
        ->once()
        ->andReturnUsing(function (string $name, float|int $value, ?string $unit, ?string $description, array $buckets, array $attributes = []) use (&$pass) {
            if ($name === SemConv::DB_QUERIES_NAME) {
                $pass = ($value === 2);
            }
        });

    Instrument::shouldReceive('flush');

    DB::select('SELECT 1');

    // Run twice to make sure it's just counting the queries in the callback
    Schedule::call(function (): void {
        DB::select('SELECT 1');
        DB::select('SELECT 1');
    })->everyMinute();

    Schedule::call(function (): void {
        DB::select('SELECT 1');
        DB::select('SELECT 1');
    })->everyMinute();

    Artisan::call('schedule:run');

    $this->assertTrue($pass);
});

it('can count queries in commands', function (): void {
    $pass = false;

    Instrument::shouldReceive('histogram')
        ->atLeast()
        ->once()
        ->andReturnUsing(function (string $name, float|int $value, ?string $unit, ?string $description, array $buckets, array $attributes = []) use (&$pass) {
            if ($name === SemConv::DB_QUERIES_NAME) {
                $pass = ($value === 2);
            }
        });

    Instrument::shouldReceive('flush');

    DB::select('SELECT 1');

    Artisan::registerCommand(new WatcherCommand());

    // Laravel doesn't fire these events in unit tests, so fire them ourselves
    Event::dispatch(new CommandStarting('watcher:command', new StringInput(''), new NullOutput()));
    Artisan::call('watcher:command');
    Event::dispatch(new CommandFinished('watcher:command', new StringInput(''), new NullOutput(), 0));

    $this->assertTrue($pass);
});
