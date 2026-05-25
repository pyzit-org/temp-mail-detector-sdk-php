<?php

declare(strict_types=1);

namespace Pyzit\TempMail\Exceptions;

/**
 * Thrown when the cURL request exceeds the configured timeout.
 */
class TimeoutException extends PyzitException
{
    public function __construct(string $message = 'Request timed out.')
    {
        parent::__construct($message);
    }
}