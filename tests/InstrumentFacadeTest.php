<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Stickee\Instrumentation\Exporters\Events\LogFile;

beforeEach(function (): void {
    $this->filename = base_path('test.log');
    Config::set('instrumentation.events_exporter', LogFile::class);
    Config::set('instrumentation.log_file.filename', $this->filename);
});

afterEach(function (): void {
    if (file_exists($this->filename)) {
        unlink($this->filename);
    }
});

it('can forward data events onto the underlying exporter', function (string $event): void {
    $log = "{$event} event";
    $exporter = app('instrument');

    $exporter->$event($log, [], 1.0);

    // Now check the logfile:
    if (! file_exists($this->filename)) {
        $this::fail('File did not get created!');
    }

    $data = collect(file($this->filename));

    expect($data->first())->toContain($log);
})->with([
    'event',
    'counter',
    'gauge',
]);


it('will forward flush onto the underlying exporter', function (): void {
    app('instrument')->flush();

    expect(true)->toBeTrue();
});
