<?php

declare(strict_types=1);

namespace Pyzit\TempMail\Exceptions;

/**
 * Thrown when the API returns HTTP 429 (Too Many Requests).
 * Wait for the number of seconds in $retryAfter before retrying.
 */
class RateLimitException extends PyzitException
{
    public function __construct(private readonly int $retryAfter = 60)
    {
        parent::__construct("Rate limit exceeded. Retry after {$retryAfter} seconds.");
    }

    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}