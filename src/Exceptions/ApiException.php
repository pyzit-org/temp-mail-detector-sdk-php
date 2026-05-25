<?php

declare(strict_types=1);

namespace Pyzit\TempMail\Exceptions;

/**
 * Thrown for any unexpected HTTP error (5xx, unknown 4xx, etc.).
 */
class ApiException extends PyzitException
{
    public function __construct(
        private readonly int $statusCode = 0,
        private readonly string $responseBody = '',
    ) {
        $preview = mb_substr($responseBody, 0, 200);
        parent::__construct("API returned HTTP {$statusCode}: {$preview}");
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getResponseBody(): string
    {
        return $this->responseBody;
    }
}