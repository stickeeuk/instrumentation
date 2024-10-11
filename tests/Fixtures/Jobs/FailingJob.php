<?php

namespace Stickee\Instrumentation\Tests\Fixtures\Jobs;

use Exception;

class FailingJob extends Job
{
    public function handle(): void
    {
        throw new Exception('Job failed');
    }
}
