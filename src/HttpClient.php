<?php

declare(strict_types=1);

namespace Pyzit\TempMail;

use Pyzit\TempMail\Exceptions\ApiException;
use Pyzit\TempMail\Exceptions\AuthenticationException;
use Pyzit\TempMail\Exceptions\PlanRequiredException;
use Pyzit\TempMail\Exceptions\RateLimitException;
use Pyzit\TempMail\Exceptions\ScopeException;
use Pyzit\TempMail\Exceptions\TimeoutException;

/**
 * Internal cURL-based HTTP layer.
 * Not part of the public API — use TempMailClient instead.
 *
 * @internal
 */
class HttpClient
{
    private const BASE_URL        = 'https://api-tempmail.pyzit.com/v1';
    private const DEFAULT_TIMEOUT = 10; // seconds

    private string $baseUrl;
    private int    $timeout;

    /** @var array<string,string> */
    private array $headers;

    public function __construct(
        string $apiToken,
        int    $timeout = self::DEFAULT_TIMEOUT,
        string $baseUrl = self::BASE_URL,
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
        $this->headers = [
            'Authorization: Bearer ' . $apiToken,
            'Content-Type: application/json',
            'Accept: application/json',
        ];
    }

    /**
     * POST JSON to $path and return the decoded response body.
     *
     * @param array<string,mixed> $body
     * @return array<string,mixed>
     *
     * @throws AuthenticationException
     * @throws ScopeException
     * @throws PlanRequiredException
     * @throws RateLimitException
     * @throws ApiException
     * @throws TimeoutException
     */
    public function post(string $path, array $body): array
    {
        $url  = $this->baseUrl . $path;
        $json = json_encode($body, JSON_THROW_ON_ERROR);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $json,
            CURLOPT_HTTPHEADER     => $this->headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_HEADER         => true,   // include response headers
        ]);

        $raw    = curl_exec($ch);
        $errno  = curl_errno($ch);
        $info   = curl_getinfo($ch);
        curl_close($ch);

        // cURL-level errors
        if ($raw === false) {
            if ($errno === CURLE_OPERATION_TIMEDOUT) {
                throw new TimeoutException();
            }
            throw new ApiException(0, "cURL error #{$errno}");
        }

        // split headers from body
        $headerSize  = (int)($info['header_size'] ?? 0);
        $rawHeaders  = substr((string)$raw, 0, $headerSize);
        $responseBody = substr((string)$raw, $headerSize);
        $statusCode  = (int)($info['http_code'] ?? 0);

        $this->raiseForStatus($statusCode, $rawHeaders, $responseBody);

        $decoded = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);
        return is_array($decoded) ? $decoded : [];
    }

    // ── private helpers ────────────────────────────────────────────

    private function raiseForStatus(int $status, string $rawHeaders, string $body): void
    {
        if ($status >= 200 && $status < 300) {
            return;
        }

        $decoded = [];
        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR) ?? [];
        } catch (\JsonException) {
            // non-JSON body — keep $decoded as empty array
        }

        match (true) {
            $status === 401 => throw new AuthenticationException(),

            $status === 403 && str_contains(strtolower((string)($decoded['detail'] ?? '')), 'scope')
                => throw new ScopeException($this->extractScope((string)($decoded['detail'] ?? ''))),

            $status === 403 && isset($decoded['required_plan'])
                => throw new PlanRequiredException((string)$decoded['required_plan']),

            $status === 403
                => throw new AuthenticationException(
                    'Access denied (403). Check your API token is valid and active.'
                ),

            $status === 402
                => throw new PlanRequiredException((string)($decoded['required_plan'] ?? 'pro')),

            $status === 429
                => throw new RateLimitException($this->parseRetryAfter($rawHeaders)),

            default => throw new ApiException($status, $body),
        };
    }

    /** Extract "detailed:tempemail_check" from a scope error message. */
    private function extractScope(string $detail): string
    {
        if (str_contains($detail, 'scope:')) {
            return trim(substr($detail, strpos($detail, 'scope:') + 6));
        }
        return '';
    }

    /** Parse Retry-After header value; defaults to 60. */
    private function parseRetryAfter(string $rawHeaders): int
    {
        if (preg_match('/Retry-After:\s*(\d+)/i', $rawHeaders, $m)) {
            return (int)$m[1];
        }
        return 60;
    }
}