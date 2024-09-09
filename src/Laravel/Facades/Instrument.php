<?php

namespace Stickee\Instrumentation\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Instrumentation Laravel facade
 */
class Instrument extends Facade
{
    /**
     * Get the facade accessor
     */
    protected static function getFacadeAccessor()
    {
        return 'instrument';
    }
}
