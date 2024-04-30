<?php

declare(strict_types=1);

use Stickee\Instrumentation\Exporters\Exporter;
use Stickee\Instrumentation\Exporters\Events\LogFile;
use Stickee\Instrumentation\Exporters\Spans\NullSpans;
use Stickee\Instrumentation\Exporters\Interfaces\EventsExporterInterface;
use Stickee\Instrumentation\Exporters\Interfaces\SpansExporterInterface;
use Stickee\Instrumentation\Instrument;

test('set database will change the internal database implementation', function (): void {
    Instrument::setExporter(new Exporter(new LogFile('test.log'), new NullSpans));

    // Check whether the internal database has actually changed using black magic:
    // Add this function to the Instrument class to get the private "exporter" member.
    $bypass = static function (): EventsExporterInterface {
        /** @noinspection PhpExpressionAlwaysNullInspection */
        return static::$exporter;
    };

    $bypass = $bypass->bindTo(null, Instrument::class);
    $database = $bypass();

    expect($database)
        ->toBeInstanceOf(EventsExporterInterface::class)
        ->toBeInstanceOf(SpansExporterInterface::class);

    // Check whether the internal database has actually changed using black magic:
    // Add this function to the $database Exporter object to get the private "eventsExporter" member.
    $bypass = function (): EventsExporterInterface {
        /** @noinspection PhpExpressionAlwaysNullInspection */
        return $this->eventsExporter;
    };

    $bypass = $bypass->bindTo($database, Exporter::class);
    $eventsExporter = $bypass();

    expect($eventsExporter)
        ->toBeInstanceOf(EventsExporterInterface::class)
        ->toBeInstanceOf(LogFile::class);
});
