<?php

declare(strict_types=1);

use Stickee\Instrumentation\Laravel\Facades\Instrument;

it('has a valid facade accessor', function (): void {
    $facade = new class () extends Instrument {
        /**
         * Return the accessor used for resolution from the service container.
         *
         * @return string
         */
        public static function getAccessor(): string
        {
            return self::getFacadeAccessor();
        }
    };

    expect($facade::getAccessor())->toContain('instrument');
});
