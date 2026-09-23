<?php

declare(strict_types=1);

namespace Pyzit\TempMail;

use Pyzit\TempMail\Exceptions\ApiException;
use Pyzit\TempMail\Exceptions\AuthenticationException;
use Pyzit\TempMail\Exceptions\InsufficientScopeError;
use Pyzit\TempMail\Exceptions\QuotaExceededError;
use Pyzit\TempMail\Exceptions\BatchLimitExceededError;
use Pyzit\TempMail\Exceptions\RateLimitException;
use Pyzit\TempMail\Exceptions\ValidationError;
use Pyzit\TempMail\Exceptions\TimeoutException;
use Pyzit\TempMail\Exceptions\ScopeException;
use Pyzit\TempMail\Exceptions\PlanRequiredException;

/**
 * Internal cURL-based HTTP layer.
 * Not part of the public API — use TempMailClient instead.
 *
 * @internal
 */
class HttpClient
{
    private const DEFAULT_TIMEOUT = 5; // seconds
    private const DEFAULT_MAX_RETRIES = 2;

    private string $baseUrl;
    private int    $timeout;
    private int    $maxRetries;
    private bool   $failOpen;

    /** @var array<string,string> */
    private array $headers;

    public function __construct(
        string $apiKey,
        string $baseUrl = 'https://api.pyzit.com',
        int    $timeout = self::DEFAULT_TIMEOUT,
        int    $maxRetries = self::DEFAULT_MAX_RETRIES,
        bool   $failOpen = false,
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
        $this->maxRetries = $maxRetries;
        $this->failOpen = $failOpen;
        $this->headers = [
            'Authorization: Bearer ' . $apiKey,
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
     * @throws InsufficientScopeError
     * @throws QuotaExceededError
     * @throws BatchLimitExceededError
     * @throws RateLimitException
     * @throws ValidationError
     * @throws ApiException
     * @throws TimeoutException
     */
    public function post(string $path, array $body): array
    {
        $attempts = 0;
        $lastError = null;

        while ($attempts <= $this->maxRetries) {
            try {
                return $this->makeRequest($path, $body);
            } catch (ApiException $e) {
                $lastError = $e;

                // Retry on 429 with exponential backoff
                if ($e->getStatusCode() === 429 && $attempts < $this->maxRetries) {
                    $retryAfter = $this->parseRetryAfter($e->getResponseHeaders() ?? '');
                    $waitTime = $retryAfter > 0 ? $retryAfter : (2 ** $attempts) + random_int(100, 400) / 1000;
                    usleep((int)($waitTime * 1_000_000));
                    $attempts++;
                    continue;
                }

                // Retry on 5xx with exponential backoff
                if ($e->getStatusCode() >= 500 && $attempts < $this->maxRetries) {
                    $waitTime = (2 ** $attempts) + random_int(100, 400) / 1000;
                    usleep((int)($waitTime * 1_000_000));
                    $attempts++;
                    continue;
                }

                // Fail-open for network errors on last attempt
                if ($this->failOpen && $attempts === $this->maxRetries) {
                    return $this->getSyntheticCleanResponse($body);
                }

                throw $e;
            } catch (TimeoutException $e) {
                $lastError = $e;

                // Retry on timeout with exponential backoff
                if ($attempts < $this->maxRetries) {
                    $waitTime = (2 ** $attempts) + random_int(100, 400) / 1000;
                    usleep((int)($waitTime * 1_000_000));
                    $attempts++;
                    continue;
                }

                // Fail-open for timeout errors
                if ($this->failOpen) {
                    return $this->getSyntheticCleanResponse($body);
                }

                throw $e;
            }
        }

        throw $lastError ?? new ApiException(0, 'Max retries exceeded', '');
    }

    /**
     * Make a single HTTP request.
     *
     * @param array<string,mixed> $body
     * @return array<string,mixed>
     *
     * @throws AuthenticationException
     * @throws InsufficientScopeError
     * @throws QuotaExceededError
     * @throws BatchLimitExceededError
     * @throws RateLimitException
     * @throws ValidationError
     * @throws ApiException
     * @throws TimeoutException
     */
    private function makeRequest(string $path, array $body): array
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
            throw new ApiException(0, "cURL error #{$errno}", '');
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

    /**
     * Get synthetic clean response for fail-open behavior.
     *
     * @param array<string,mixed> $body
     * @return array<string,mixed>
     */
    private function getSyntheticCleanResponse(array $body): array
    {
        return [
            'email' => $body['email'] ?? '',
            'is_disposable' => false,
            'status' => 'clean',
        ];
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

        $detail = (string)($decoded['detail'] ?? '');
        $code = (string)($decoded['code'] ?? '');
        $requiredPlan = (string)($decoded['required_plan'] ?? '');

        match (true) {
            $status === 401 => throw new AuthenticationException($detail ?: 'Invalid or inactive API key.'),

            $status === 402 => throw new PlanRequiredException(
                $requiredPlan ?: 'pro',
                $detail ?: 'This feature requires a paid plan.'
            ),

            $status === 403 => $this->handle403($decoded, $detail, $requiredPlan),

            $status === 400 => $this->handleBadRequest($decoded, $detail),

            $status === 429 => throw new RateLimitException(
                $this->parseRetryAfter($rawHeaders),
                $detail ?: 'Request throttled.'
            ),

            default => throw new ApiException($status, $body, $rawHeaders),
        };
    }

    /**
     * Handle 403 Forbidden errors.
     *
     * @param array<string,mixed> $decoded
     * @param string $detail
     * @param string $requiredPlan
     * @return never
     *
     * @throws ScopeException
     * @throws PlanRequiredException
     * @throws InsufficientScopeError
     * @throws AuthenticationException
     */
    private function handle403(array $decoded, string $detail, string $requiredPlan): void
    {
        // Check for required plan
        if ($requiredPlan !== '') {
            throw new PlanRequiredException($requiredPlan, $detail ?: 'This feature requires a higher plan.');
        }

        // Check for scope error in detail message
        if (str_contains($detail, 'scope') || str_contains($detail, 'required scope')) {
            $scope = $this->parseRequiredScope($detail);
            throw new ScopeException($scope, $detail ?: 'Token missing required scope.');
        }

        // Fallback to authentication error
        throw new AuthenticationException($detail ?: 'API key lacks required scope.');
    }

    /**
     * Handle 400 Bad Request errors.
     *
     * @param array<string,mixed> $decoded
     * @param string $detail
     * @return never
     *
     * @throws BatchLimitExceededError
     * @throws ValidationError
     * @throws ApiException
     */
    private function handleBadRequest(array $decoded, string $detail): void
    {
        // Check for batch limit error
        if (str_contains(strtolower($detail), 'maximum of') && str_contains(strtolower($detail), 'bulk request')) {
            $maxAllowed = $this->parseMaxAllowed($detail);
            throw new BatchLimitExceededError($maxAllowed, 0, $detail);
        }

        // Check for validation errors (field errors)
        if (!empty($decoded) && !$this->isSimpleDetail($decoded)) {
            throw new ValidationError($decoded, $detail ?: 'Validation error.');
        }

        // Generic validation error
        throw new ValidationError([], $detail ?: 'Validation error.');
    }

    /**
     * Check if the decoded response is a simple detail message.
     *
     * @param array<string,mixed> $decoded
     * @return bool
     */
    private function isSimpleDetail(array $decoded): bool
    {
        return isset($decoded['detail']) && count($decoded) === 1;
    }

    /**
     * Parse quota type from error message.
     *
     * @param string $detail
     * @return string
     */
    private function parseQuotaType(string $detail): string
    {
        return stripos($detail, 'detailed') !== false ? 'detailed' : 'simple';
    }

    /**
     * Parse required scope from error message.
     *
     * @param string $detail
     * @return string
     */
    private function parseRequiredScope(string $detail): string
    {
        if (str_contains($detail, 'tmd:detailed')) {
            return 'tmd:detailed';
        }
        if (str_contains($detail, 'tmd:basic')) {
            return 'tmd:basic';
        }
        // Try to extract scope from quoted string
        if (preg_match("/'([^']+)'/", $detail, $matches)) {
            return $matches[1];
        }
        // Try to extract scope after "scope:" or "required scope:"
        if (preg_match('/scope:\s*(\S+)/i', $detail, $matches)) {
            return $matches[1];
        }
        if (preg_match('/required scope:\s*(\S+)/i', $detail, $matches)) {
            return $matches[1];
        }
        // Try to extract the last word after colon (for "required scope: bulk:validate")
        if (preg_match('/:\s*(\S+)$/', $detail, $matches)) {
            return $matches[1];
        }
        return '';
    }

    /**
     * Parse max allowed batch size from error message.
     *
     * @param string $detail
     * @return int
     */
    private function parseMaxAllowed(string $detail): int
    {
        if (preg_match('/maximum of (\d+)/i', $detail, $matches)) {
            return (int)$matches[1];
        }
        return 10; // default fallback
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