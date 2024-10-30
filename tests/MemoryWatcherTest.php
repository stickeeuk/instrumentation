<?php

declare(strict_types=1);

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schedule;
use Stickee\Instrumentation\Laravel\Facades\Instrument;
use Stickee\Instrumentation\Tests\Fixtures\WatcherCommand;
use Stickee\Instrumentation\Tests\Fixtures\WatcherJob;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;

it('can watch memory in jobs', function (): void {
    $pass = false;

    Instrument::partialMock()
        ->allows('counter') // TODO why do we need to ignore the counter registered in ISP?
        ->shouldReceive('histogram')
        ->atLeast()
        ->once()
        ->andReturnUsing(function (string $name, ?string $unit, ?string $description, array $buckets = [], array $attributes = [], float|int $value) use (&$pass) {
            if ($name === 'process.memory.usage') {
                $pass = ($value > 10) && ($value < 100);
            }
        });

    Instrument::shouldReceive('flush');

    // Dispatch the job twice to make sure it's just counting memory used in the job
    WatcherJob::dispatch(100);
    WatcherJob::dispatch(10);

    $this->assertTrue($pass);
});

it('can watch memory in schedules', function (): void {
    $pass = false;

    Instrument::shouldReceive('histogram')
        ->atLeast()
        ->once()
        ->andReturnUsing(function (string $name, ?string $unit, ?string $description, array $buckets = [], array $attributes = [], float|int $value) use (&$pass) {
            if ($name === 'process.memory.usage') {
                $pass = ($value > 10) && ($value < 100);
            }
        });

    Instrument::shouldReceive('flush');

    // Run twice to make sure it's just counting memory used in the callback
    Schedule::call(function (): void {
        str_repeat(' ', 100 * 1024 * 1024);
    })->everyMinute();

    Schedule::call(function (): void {
        str_repeat(' ', 10 * 1024 * 1024);
    })->everyMinute();

    Artisan::call('schedule:run');

    $this->assertTrue($pass);
});

it('can watch memory in commands', function (): void {
    $pass = false;

    Instrument::shouldReceive('histogram')
        ->atLeast()
        ->once()
        ->andReturnUsing(function (string $name, ?string $unit, ?string $description, array $buckets = [], array $attributes = [], float|int $value) use (&$pass) {
            if ($name === 'process.memory.usage') {
                $pass = ($value > 10) && ($value < 100);
            }
        });

    Instrument::shouldReceive('flush');

    Artisan::registerCommand(new WatcherCommand());

    str_repeat(' ', 100 * 1024 * 1024);

    // Laravel doesn't fire these events in unit tests, so fire them ourselves
    Event::dispatch(new CommandStarting('watcher:command', new StringInput(''), new NullOutput()));
    Artisan::call('watcher:command');
    Event::dispatch(new CommandFinished('watcher:command', new StringInput(''), new NullOutput(), 0));

    $this->assertTrue($pass);
});
