<?php

declare(strict_types=1);

namespace Pyzit\TempMail\Exceptions;

/**
 * Thrown when the API returns HTTP 403 due to a missing token scope.
 * Enable the required scope in your Pyzit dashboard under API Tokens.
 */
class ScopeException extends PyzitException
{
    public function __construct(private readonly string $requiredScope = '')
    {
        $msg = $requiredScope !== ''
            ? "Token missing required scope: '{$requiredScope}'. Enable it in your Pyzit dashboard."
            : 'Token is missing a required scope. Check your Pyzit dashboard.';
        parent::__construct($msg);
    }

    public function getRequiredScope(): string
    {
        return $this->requiredScope;
    }
}