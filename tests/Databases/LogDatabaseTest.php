<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use phpmock\phpunit\PHPMock;
use Stickee\Instrumentation\Databases\Log;
use Stickee\Instrumentation\Exceptions\DatabaseWriteException;

const LOG_EVENT = 'Event';

uses(PHPMock::class);

beforeEach(function (): void {
    $this->logFile = base_path('test.log');
    Config::set('instrumentation.filename', $this->logFile);

    $this->database = app(Log::class);

    if (file_exists($this->logFile)) {
        rename($this->logFile, $this->logFile . '.backup');
    }
});

it('will handle any exception thrown whilst attempting to write to the log file', function (array $tags): void {
    $this
        ->getFunctionMock('\\Stickee\\Instrumentation\\Databases\\', 'fopen')
        ->expects($this::once())
        ->withAnyParameters()
        ->willThrowException(new Exception('Not enough disk space!'));

    // Without an overridden handler, we will expect this exception thrown, not \Exception.
    $this->expectException(DatabaseWriteException::class);

    // For this test, we'll create another log database class.
    $log = new Log($this->logFile);
    $log->event(LOG_EVENT, $tags);
})->with('writable values');


it('can write a file to a local log file', function (array $tags): void {
    $this::assertFileDoesNotExist($this->logFile);

    $this->database->event(LOG_EVENT, $tags);

    $this::assertFileExists($this->logFile);

    $readFile = file($this->logFile);

    expect($readFile)
        ->toBeArray()
        ->toHaveCount(1)
        ->and(head($readFile))
            ->toContain(LOG_EVENT);

    unlink($this->logFile);

    $this::assertFileDoesNotExist($this->logFile);
})->with('writable values');
