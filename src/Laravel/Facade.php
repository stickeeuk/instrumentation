<?php

namespace Stickee\Instrumentation\Laravel;

use Illuminate\Support\Facades\Facade as BaseFacade;

/**
 * Instrumentation Laravel facade
 */
class Facade extends BaseFacade
{
    /**
     * Get the facade accessor
     */
    protected static function getFacadeAccessor()
    {
        return 'instrument';
    }
}
