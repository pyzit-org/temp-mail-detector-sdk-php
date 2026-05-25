<?php
 
declare(strict_types=1);
 
namespace Pyzit\TempMail\Exceptions;
 
/**
 * Thrown when the API returns HTTP 401.
 * The provided API token is invalid, expired, or missing.
 */
class AuthenticationException extends PyzitException
{
    public function __construct(string $message = 'Invalid or missing API token.')
    {
        parent::__construct($message);
    }
}
 