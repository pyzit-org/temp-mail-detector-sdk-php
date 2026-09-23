<?php

declare(strict_types=1);

namespace Pyzit\TempMail\Exceptions;

/**
 * Raised on HTTP 403 when token lacks required scope.
 * 
 * This is a more specific version of InsufficientScopeError that includes
 * the actual scope string that was required.
 */
final class ScopeException extends InsufficientScopeError
{
    private string $requiredScope;

    public function __construct(string $requiredScope = "", string $message = "")
    {
        $this->requiredScope = $requiredScope;
        parent::__construct(
            $message ?: "Token missing required scope: {$requiredScope}",
            $requiredScope
        );
    }

    public function getRequiredScope(): string
    {
        return $this->requiredScope;
    }
}
