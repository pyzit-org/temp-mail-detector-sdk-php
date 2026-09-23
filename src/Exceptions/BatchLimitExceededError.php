<?php

declare(strict_types=1);

namespace Pyzit\TempMail\Exceptions;

/**
 * Thrown when the API returns HTTP 400 for bulk batch size exceeding plan limit.
 */
class BatchLimitExceededError extends PyzitException
{
    public function __construct(
        private readonly int $maxAllowed = 10,
        private readonly int $attempted = 0,
        string $message = 'Bulk batch size exceeds plan limit.'
    ) {
        parent::__construct($message);
    }

    public function getMaxAllowed(): int
    {
        return $this->maxAllowed;
    }

    public function getAttempted(): int
    {
        return $this->attempted;
    }
}
