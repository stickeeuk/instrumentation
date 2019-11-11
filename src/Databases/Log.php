<?php

namespace Stickee\Instrumentation\Databases;

use Exception;
use Stickee\Instrumentation\Databases\Traits\HandlesErrors;
use Stickee\Instrumentation\Databases\Traits\WritesStrings;

/**
 * This class records metrics to a log file
 */
class Log implements DatabaseInterface
{
    use HandlesErrors, WritesStrings;

    /**
     * The log file name
     *
     * @var string $filename
     */
    private $filename;

    /**
     * Constructor
     *
     * @param string $filename The log file name
     */
    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }

    /**
     * Write to the file
     *
     * @param string $message The message to write
     */
    protected function write($message): void
    {
        try {
            $f = fopen($this->filename, 'a');
            fwrite($f, $message . "\n");
            fclose($f);
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }
}
