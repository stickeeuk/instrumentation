<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;

beforeEach(function (): void {
    Config::set('instrumentation.dsn', $this::EXAMPLE_DSN);
});


