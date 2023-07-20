<?php

declare(strict_types=1);

namespace Stickee\Instrumentation\Tests\Fixtures;

final class BadDatabase
{
    // A class that wants to be a database... but doesn't implement DatabaseInterface.
}
