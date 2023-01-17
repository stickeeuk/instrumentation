<?php

declare(strict_types=1);

use phpmock\phpunit\PHPMock;
use Stickee\Instrumentation\Databases\Traits\HandlesErrors;
use Stickee\Instrumentation\Exceptions\DatabaseWriteException;

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
    };
});

it('can set an error handler', function (): void {
    $method = 'var_dump';
    $this->database->setErrorHandler($method);

    $bypass = function (): ?string {
        return $this->errorHandler; // Ignore any IDE/Canary errors - this is fine! (But naughty magic)
    };

    $bypass = $bypass->bindTo($this->database, $this->database);
    $handler = $bypass();

    expect($handler)->toEqual($method);
});

it('can call a custom error handler', function (): void {
    $method = 'var_dump';
    $this->database->setErrorHandler($method);

    $this
        ->getFunctionMock('\\Stickee\\Instrumentation\\Databases\\Traits\\', 'call_user_func')
        ->expects($this::once())
        ->with('var_dump', new Exception())
        ->willReturn(null);

    $this->database->invokeErrorHandler();
});

it('will throw an exception if no custom error handler is set', function (): void {
    $this->database->invokeErrorHandler();
})->throws(DatabaseWriteException::class);
