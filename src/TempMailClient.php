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
     * @param string $apiToken  Your Pyzit API token.
     * @param int    $timeout   Request timeout in seconds. Default: 10.
     * @param string $baseUrl   Override the API base URL (useful for tests).
     */
    public function __construct(
        string $apiToken,
        int    $timeout = 10,
        string $baseUrl = 'https://api-tempmail.pyzit.com/v1',
    ) {
        $this->http = new HttpClient($apiToken, $timeout, $baseUrl);
    }

    /**
     * Quick disposable check — free tier.
     *
     * POST /v1/validate/check/
     *
     * @param string $email The email address to validate.
     * @return CheckResult
     *
     * @throws \Pyzit\TempMail\Exceptions\AuthenticationException
     * @throws \Pyzit\TempMail\Exceptions\ScopeException
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
        $data = $this->http->post('/validate/check/', ['email' => $email]);
        return CheckResult::fromArray($data);
    }

    /**
     * Full DNS + reputation analysis — Pro tier.
     *
     * POST /v1/validate/detailed/
     *
     * @param string $email The email address to analyse.
     * @return DetailedResult
     *
     * @throws \Pyzit\TempMail\Exceptions\PlanRequiredException   if not on Pro plan.
     * @throws \Pyzit\TempMail\Exceptions\ScopeException          if token scope is missing.
     * @throws \Pyzit\TempMail\Exceptions\AuthenticationException
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
        $data = $this->http->post('/validate/detailed/', ['email' => $email]);
        return DetailedResult::fromArray($data);
    }

    /**
     * Validate many emails in one API call — Business tier.
     *
     * POST /v1/validate/bulk/
     *
     * @param string[] $emails Array of email addresses (max 100 per request).
     * @return BulkResult
     *
     * @throws \Pyzit\TempMail\Exceptions\PlanRequiredException   if not on Business plan.
     * @throws \Pyzit\TempMail\Exceptions\ScopeException
     * @throws \Pyzit\TempMail\Exceptions\AuthenticationException
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
        $data = $this->http->post('/validate/bulk/', ['emails' => $emails]);
        return BulkResult::fromArray($data);
    }
}