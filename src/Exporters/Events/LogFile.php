<?php

namespace Stickee\Instrumentation\Exporters\Events;

use Exception;
use Stickee\Instrumentation\Exporters\Events\Traits\WritesStrings;
use Stickee\Instrumentation\Exporters\Interfaces\EventsExporterInterface;
use Stickee\Instrumentation\Exporters\Traits\HandlesErrors;

/**
 * This class records metrics to a log file
 */
class LogFile implements EventsExporterInterface
{
    use HandlesErrors;
    use WritesStrings;

    /**
     * The log file name
     */
    private string $filename;

    /**
     * Constructor
     *
     * @param string $filename The log file name
     */
    public function __construct(string $filename)
    {
        if ($filename === '') {
            throw new Exception('Filename not specified');
        }

        $this->filename = $filename;
    }

    /**
     * Write to the file
     *
     * @param string $message The message to write
     */
    protected function write(string $message): void
    {
        try {
            $f = fopen($this->filename, 'ab');
            fwrite($f, $message . "\n");
            fclose($f);
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }
}
