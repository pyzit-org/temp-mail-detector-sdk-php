<?php

declare(strict_types=1);

namespace Pyzit\TempMail\Exceptions;

/**
 * Thrown when the API returns HTTP 403 due to missing token scope.
 * Token lacks required scope (e.g., 'tmd:basic' or 'tmd:detailed').
 */
class InsufficientScopeError extends PyzitException
{
    public function __construct(
        private readonly string $requiredScope = '',
        string $message = 'API key lacks required scope.'
    ) {
        $msg = $requiredScope !== ''
            ? "API key lacks required scope: '{$requiredScope}'."
            : $message;
        parent::__construct($msg);
    }

    public function getRequiredScope(): string
    {
        return $this->requiredScope;
    }
}
