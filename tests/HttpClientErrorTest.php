<?php

declare(strict_types=1);

namespace Pyzit\TempMail\Tests;

use PHPUnit\Framework\TestCase;
use Pyzit\TempMail\HttpClient;
use Pyzit\TempMail\Exceptions\AuthenticationException;
use Pyzit\TempMail\Exceptions\ApiException;
use Pyzit\TempMail\Exceptions\QuotaExceededError;
use Pyzit\TempMail\Exceptions\InsufficientScopeError;
use Pyzit\TempMail\Exceptions\BatchLimitExceededError;
use Pyzit\TempMail\Exceptions\RateLimitException;
use Pyzit\TempMail\Exceptions\ValidationError;

class HttpClientErrorTest extends TestCase
{
    private function callRaiseForStatus(int $status, string $headers, string $body): void
    {
        $client = new HttpClient('tok');
        $method = new \ReflectionMethod(HttpClient::class, 'raiseForStatus');
        $method->invoke($client, $status, $headers, $body);
    }

    public function testStatus200DoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();
        $this->callRaiseForStatus(200, '', '{"ok":true}');
    }

    public function testStatus201DoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();
        $this->callRaiseForStatus(201, '', '{"created":true}');
    }

    public function testStatus401ThrowsAuthenticationException(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->callRaiseForStatus(401, '', '{}');
    }

    public function testStatus403WithScopeDetailThrowsInsufficientScopeError(): void
    {
        $this->expectException(InsufficientScopeError::class);
        $body = json_encode(['detail' => "API key lacks required scope: 'tmd:detailed'."]);
        $this->callRaiseForStatus(403, '', $body);
    }

    public function testStatus403ScopeExceptionContainsScope(): void
    {
        $body = json_encode(['detail' => "API key lacks required scope: 'tmd:basic'."]);
        try {
            $this->callRaiseForStatus(403, '', $body);
            $this->fail('Expected InsufficientScopeError');
        } catch (InsufficientScopeError $e) {
            $this->assertSame('tmd:basic', $e->getRequiredScope());
        }
    }

    public function testStatus402ThrowsQuotaExceededError(): void
    {
        $this->expectException(QuotaExceededError::class);
        $body = json_encode([
            'detail' => 'You have exceeded your monthly detailed check quota. Please upgrade your plan.',
            'code' => 'quota_exceeded',
        ]);
        $this->callRaiseForStatus(402, '', $body);
    }

    public function testStatus402ParsesQuotaType(): void
    {
        $body = json_encode([
            'detail' => 'You have exceeded your monthly detailed check quota.',
        ]);
        try {
            $this->callRaiseForStatus(402, '', $body);
            $this->fail('Expected QuotaExceededError');
        } catch (QuotaExceededError $e) {
            $this->assertSame('detailed', $e->getQuotaType());
        }
    }

    public function testStatus400BatchLimitThrowsBatchLimitExceededError(): void
    {
        $this->expectException(BatchLimitExceededError::class);
        $body = json_encode([
            'detail' => 'Your plan allows a maximum of 10 emails per bulk request. Upgrade your plan for higher limits.',
        ]);
        $this->callRaiseForStatus(400, '', $body);
    }

    public function testStatus429ThrowsRateLimitException(): void
    {
        $this->expectException(RateLimitException::class);
        $this->callRaiseForStatus(429, "HTTP/1.1 429 Too Many Requests\r\nRetry-After: 45\r\n", '{}');
    }

    public function testStatus429ParsesRetryAfterHeader(): void
    {
        try {
            $headers = "HTTP/1.1 429 Too Many Requests\r\nRetry-After: 30\r\n";
            $this->callRaiseForStatus(429, $headers, '{}');
            $this->fail('Expected RateLimitException');
        } catch (RateLimitException $e) {
            $this->assertSame(30, $e->getRetryAfter());
        }
    }

    public function testStatus429DefaultsRetryAfterTo60(): void
    {
        try {
            $this->callRaiseForStatus(429, '', '{}');
            $this->fail('Expected RateLimitException');
        } catch (RateLimitException $e) {
            $this->assertSame(60, $e->getRetryAfter());
        }
    }

    public function testStatus500ThrowsApiException(): void
    {
        $this->expectException(ApiException::class);
        $this->callRaiseForStatus(500, '', 'Internal Server Error');
    }

    public function testStatus503StoresStatusCode(): void
    {
        try {
            $this->callRaiseForStatus(503, '', 'Service Unavailable');
            $this->fail('Expected ApiException');
        } catch (ApiException $e) {
            $this->assertSame(503, $e->getStatusCode());
            $this->assertSame('Service Unavailable', $e->getResponseBody());
        }
    }

    public function testNonJsonBodyStillParsed(): void
    {
        $this->expectException(ApiException::class);
        $this->callRaiseForStatus(500, '', 'plain text error');
    }
}
