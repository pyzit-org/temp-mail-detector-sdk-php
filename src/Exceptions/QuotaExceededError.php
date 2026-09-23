<?php

declare(strict_types=1);

namespace Pyzit\TempMail\Exceptions;

/**
 * Thrown when the API returns HTTP 402 (Payment Required).
 * Monthly simple or detailed quota has been exhausted.
 */
class QuotaExceededError extends PyzitException
{
    public function __construct(
        private readonly string $quotaType = 'simple',
        string $message = 'Monthly quota exceeded. Please upgrade your plan.'
    ) {
        parent::__construct($message);
    }

    public function getQuotaType(): string
    {
        return $this->quotaType;
    }
}
