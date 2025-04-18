<?php

declare(strict_types=1);

use phpmock\mockery\PHPMockery;
use Stickee\Instrumentation\Exceptions\WriteException;
use Stickee\Instrumentation\Exporters\Traits\HandlesErrors;

beforeEach(function () {
    $this->exporter = new class {
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
    $this->exporter->setErrorHandler($method);

    expect($this->exporter->getErrorHandler())->toEqual($method);
});

it('can still retrieve the error handler even if it is not set', function (): void {
    $handler = $this->exporter->getErrorHandler();

    expect($handler)->toBeNull();
});

it('can call a custom error handler', function (): void {
    $method = 'var_dump';
    $this->exporter->setErrorHandler($method);

    PHPMockery::mock('Stickee\\Instrumentation\\Exporters\\Traits', 'call_user_func')
        ->with($method, \Mockery::type(Exception::class))
        ->once()
        ->andReturn(null);

    $this->exporter->invokeErrorHandler();
});

it('will throw an exception if no custom error handler is set', function (): void {
    $this->exporter->invokeErrorHandler();
})->throws(WriteException::class);
