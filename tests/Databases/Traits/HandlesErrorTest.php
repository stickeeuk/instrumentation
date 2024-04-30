<?php

declare(strict_types=1);

use phpmock\phpunit\PHPMock;
use Stickee\Instrumentation\Exceptions\WriteException;
use Stickee\Instrumentation\Exporters\Traits\HandlesErrors;

uses(PHPMock::class);

beforeEach(function () {
    $this->database = new class () {
        use HandlesErrors;

        /**
         * Invoke the internal error handler.
         *
         * @return void
         */
        public function invokeErrorHandler(): void
        {
            $this->handleError(new Exception());
        }

        /**
         * Fetch the error handler, for testing.
         *
         * @return mixed Returns the value from setErrorHandler().
         */
        public function getErrorHandler()
        {
            return $this->errorHandler;
        }
    };
});

it('can set an error handler and view its contents', function (): void {
    $method = 'var_dump';
    $this->database->setErrorHandler($method);

    expect($this->database->getErrorHandler())->toEqual($method);
});

it('can still retrieve the error handler even if it is not set', function (): void {
   $handler = $this->database->getErrorHandler();

   expect($handler)->toBeNull();
});

it('can call a custom error handler', function (): void {
    $method = 'var_dump';
    $this->database->setErrorHandler($method);

    $this
        ->getFunctionMock('\\Stickee\\Instrumentation\\Exporters\\Traits\\', 'call_user_func')
        ->expects($this::once())
        ->with('var_dump', new Exception())
        ->willReturn(null);

    $this->database->invokeErrorHandler();
});

it('will throw an exception if no custom error handler is set', function (): void {
    $this->database->invokeErrorHandler();
})->throws(WriteException::class);
