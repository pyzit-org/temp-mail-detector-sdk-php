<?php

declare(strict_types=1);

namespace Pyzit\TempMail\Exceptions;

/**
 * Thrown when the API returns HTTP 400 for validation errors.
 * Malformed email syntax or invalid request payload.
 */
class ValidationError extends PyzitException
{
    public function __construct(
        private readonly array $fieldErrors = [],
        string $message = 'Validation error.'
    ) {
        parent::__construct($message);
    }

    public function getFieldErrors(): array
    {
        return $this->fieldErrors;
    }
}
