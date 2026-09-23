<?php

declare(strict_types=1);

namespace Pyzit\TempMail;

use Pyzit\TempMail\Models\BulkResult;
use Pyzit\TempMail\Models\CheckResult;
use Pyzit\TempMail\Models\DetailedResult;

/**
 * Official PHP client for the Pyzit disposable email detector API.
 *
 * @link https://temp-mail-detector.pyzit.com
 *
 * Basic usage:
 *
 *   $client = new TempMailClient('YOUR_API_TOKEN');
 *   $result = $client->check('user@example.com');
 *
 *   if ($result->isDisposable) {
 *       throw new \RuntimeException('Disposable emails are not allowed.');
 *   }
 */
final class TempMailClient
{
    private HttpClient $http;

    /**
     * @param string $apiKey    Your Pyzit API token (pyzit_live_...).
     * @param string $baseUrl   Override the API base URL. Default: https://api.pyzit.com.
     * @param int    $timeout   Request timeout in seconds. Default: 5.
     * @param int    $maxRetries Max retry attempts on 429 and 5xx. Default: 2.
     * @param bool   $failOpen  When true, network errors return synthetic clean results. Default: false.
     */
    public function __construct(
        string $apiKey,
        string $baseUrl = 'https://api.pyzit.com',
        int    $timeout = 5,
        int    $maxRetries = 2,
        bool   $failOpen = false,
    ) {
        $this->http = new HttpClient($apiKey, $baseUrl, $timeout, $maxRetries, $failOpen);
    }

    /**
     * Quick disposable check.
     *
     * POST /v1/tmd/check/
     *
     * @param string $email The email address to validate.
     * @return CheckResult
     *
     * @throws \Pyzit\TempMail\Exceptions\AuthenticationException
     * @throws \Pyzit\TempMail\Exceptions\InsufficientScopeError
     * @throws \Pyzit\TempMail\Exceptions\QuotaExceededError
     * @throws \Pyzit\TempMail\Exceptions\RateLimitException
     * @throws \Pyzit\TempMail\Exceptions\ApiException
     * @throws \Pyzit\TempMail\Exceptions\TimeoutException
     *
     * @example
     * $r = $client->check('user@mailnator.com');
     * // $r->isDisposable === true
     * // $r->status       === 'disposable'
     */
    public function check(string $email): CheckResult
    {
        $data = $this->http->post('/v1/tmd/check/', ['email' => $email]);
        return CheckResult::fromArray($data);
    }

    /**
     * Full DNS + reputation analysis.
     *
     * POST /v1/tmd/detailed/
     *
     * @param string $email The email address to analyse.
     * @return DetailedResult
     *
     * @throws \Pyzit\TempMail\Exceptions\AuthenticationException
     * @throws \Pyzit\TempMail\Exceptions\InsufficientScopeError
     * @throws \Pyzit\TempMail\Exceptions\QuotaExceededError
     * @throws \Pyzit\TempMail\Exceptions\RateLimitException
     * @throws \Pyzit\TempMail\Exceptions\ApiException
     * @throws \Pyzit\TempMail\Exceptions\TimeoutException
     *
     * @example
     * $r = $client->detailed('user@example.com');
     * if ($r->shouldReject()) {
     *     throw new \RuntimeException('Email rejected: ' . $r->riskLevel);
     * }
     */
    public function detailed(string $email): DetailedResult
    {
        $data = $this->http->post('/v1/tmd/detailed/', ['email' => $email]);
        return DetailedResult::fromArray($data);
    }

    /**
     * Validate many emails in one API call.
     *
     * POST /v1/tmd/bulk/
     *
     * @param string[] $emails Array of email addresses.
     * @return BulkResult
     *
     * @throws \Pyzit\TempMail\Exceptions\AuthenticationException
     * @throws \Pyzit\TempMail\Exceptions\InsufficientScopeError
     * @throws \Pyzit\TempMail\Exceptions\QuotaExceededError
     * @throws \Pyzit\TempMail\Exceptions\BatchLimitExceededError
     * @throws \Pyzit\TempMail\Exceptions\RateLimitException
     * @throws \Pyzit\TempMail\Exceptions\ApiException
     * @throws \Pyzit\TempMail\Exceptions\TimeoutException
     *
     * @example
     * $r = $client->bulk(['a@x.com', 'b@y.com']);
     * $blocked = $r->disposableEmails();  // ['b@y.com']
     */
    public function bulk(array $emails): BulkResult
    {
        $data = $this->http->post('/v1/tmd/bulk/', ['emails' => $emails]);
        return BulkResult::fromArray($data);
    }
}