<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use phpmock\phpunit\PHPMock;
use Stickee\Instrumentation\Exceptions\WriteException;
use Stickee\Instrumentation\Exporters\Events\LogFile;

const LOG_EVENT = 'Event';

uses(PHPMock::class);

beforeEach(function (): void {
    $this->logFile = base_path('test.log');
    Config::set('instrumentation.log_file.filename', $this->logFile);

    $this->exporter = app(LogFile::class);

    if (file_exists($this->logFile)) {
        rename($this->logFile, $this->logFile . '.backup');
    }
});

it('will handle any exception thrown whilst attempting to write to the log file', function (array $attributes): void {
    $this
        ->getFunctionMock('\\Stickee\\Instrumentation\\Exporters\\Events\\', 'fopen')
        ->expects($this::once())
        ->withAnyParameters()
        ->willThrowException(new Exception('Not enough disk space!'));

    // Without an overridden handler, we will expect this exception thrown, not \Exception.
    $this->expectException(WriteException::class);

    // For this test, we'll create another log exporter class.
    $log = new LogFile($this->logFile);
    $log->event(LOG_EVENT, $attributes);
})->with('writable values');


it('can write a file to a local log file', function (array $attributes): void {
    $this::assertFileDoesNotExist($this->logFile);

    $this->exporter->event(LOG_EVENT, $attributes);

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
