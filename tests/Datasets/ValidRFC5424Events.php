<?php

declare(strict_types=1);

use Psr\Log\LogLevel;

dataset('valid rfc 5424 events', [
    '0 - Emergency' => [LogLevel::EMERGENCY],
    '1 - Alert' => [LogLevel::ALERT],
    '2 - Critical' => [LogLevel::CRITICAL],
    '3 - Error' => [LogLevel::ERROR],
    '4 - Warning' => [LogLevel::WARNING],
    '5 - Notice' => [LogLevel::NOTICE],
    '6 - Informational' => [LogLevel::INFO],
    '7 - Debug' => [LogLevel::DEBUG],
]);
