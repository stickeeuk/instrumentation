<?php

declare(strict_types=1);

namespace Stickee\Instrumentation\Tests\Fixtures;

use Exception;
use Stickee\Instrumentation\Databases\DatabaseInterface;

final class GoodDatabase implements DatabaseInterface
{
    private $errorHandler;

    /**
     * For test purposes, create an exception and call the handler.
     *
     * @param string $exception
     *
     * @return void
     */
    public function testErrorHandler(string $exception): void
    {
        if (is_callable($this->errorHandler)) {
            call_user_func($this->errorHandler, new Exception($exception));
        }
    }

    /** @inheritDoc */
    public function setErrorHandler($errorHandler): void
    {
        $this->errorHandler = $errorHandler;
    }

    /** @inheritDoc */
    public function getErrorHandler()
    {
        return $this->errorHandler;
    }

    /** @inheritDoc */
    public function event(string $name, array $tags = []): void
    {
        // Do nothing.
    }

    /** @inheritDoc */
    public function count(string $event, array $tags = [], float $increase = 1): void
    {
        // Do nothing.
    }

    /** @inheritDoc */
    public function gauge(string $name, array $tags, float $value): void
    {
        // Do nothing.
    }

    /** @inheritDoc */
    public function flush(): void
    {
        // Do nothing.
    }
}
