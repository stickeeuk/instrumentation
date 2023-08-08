<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Stickee\Instrumentation\Exporters\Events\Log;

beforeEach(function (): void {
    $this->filename = base_path('test.log');
    Config::set('instrumentation.database', Log::class);
    Config::set('instrumentation.filename', $this->filename);
});

afterEach(function (): void {
    if (file_exists($this->filename)) {
        unlink($this->filename);
    }
});

it('can forward data events onto the underlying database', function (string $event): void {
    $log = "{$event} event";
    $shrike = app('instrument');

    $shrike->$event($log, [], 1.0);

    // Now check the logfile:
    if (! file_exists($this->filename)) {
        $this::fail('File did not get created!');
    }

    $data = collect(file($this->filename));

    expect($data->first())->toContain($log);
})->with([
    'event',
    'count',
    'gauge',
]);


it('will forward flush onto the underlying database', function (): void {
    try {
        app('instrument')->flush();
    } catch (Throwable $throwable) {
        $this::fail('Failed to flush underlying database.');
    }

    expect(true)->toBeTrue();
});
