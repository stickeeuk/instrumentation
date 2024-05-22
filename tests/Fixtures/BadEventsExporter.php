<?php

declare(strict_types=1);

namespace Stickee\Instrumentation\Tests\Fixtures;

final class BadEventsExporter
{
    // A class that wants to be an EventsExporter but doesn't implement EventsExporterInterface.
}
