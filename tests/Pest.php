<?php

declare(strict_types=1);

use Stickee\Instrumentation\Tests\TestCase;

putenv('OTEL_PHP_AUTOLOAD_ENABLED=true'); // enable Instrumentation

uses(TestCase::class)->in(__DIR__);
