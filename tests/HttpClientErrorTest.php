<?php

declare(strict_types=1);

namespace Pyzit\TempMail\Tests;

use PHPUnit\Framework\TestCase;
use Pyzit\TempMail\HttpClient;
use Pyzit\TempMail\Exceptions\AuthenticationException;
use Pyzit\TempMail\Exceptions\ApiException;
use Pyzit\TempMail\Exceptions\PlanRequiredException;
use Pyzit\TempMail\Exceptions\RateLimitException;
use Pyzit\TempMail\Exceptions\ScopeException;

/**
 * Unit tests for HttpClient::raiseForStatus via a testable subclass.
 * We expose the private method through reflection to test HTTP error mapping.
 */
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

    public function testStatus403WithScopeDetailThrowsScopeException(): void
    {
        $this->expectException(ScopeException::class);
        $body = json_encode(['detail' => 'Token missing required scope: detailed:tempemail_check']);
        $this->callRaiseForStatus(403, '', $body);
    }

    public function testStatus403ScopeExceptionContainsScope(): void
    {
        $body = json_encode(['detail' => 'Token missing required scope: bulk:validate']);
        try {
            $this->callRaiseForStatus(403, '', $body);
            $this->fail('Expected ScopeException');
        } catch (ScopeException $e) {
            $this->assertSame('bulk:validate', $e->getRequiredScope());
        }
    }

    public function testStatus403WithRequiredPlanThrowsPlanRequiredException(): void
    {
        $this->expectException(PlanRequiredException::class);
        $body = json_encode(['required_plan' => 'pro']);
        $this->callRaiseForStatus(403, '', $body);
    }

    public function testStatus403WithRequiredPlanStoresPlan(): void
    {
        $body = json_encode(['required_plan' => 'business']);
        try {
            $this->callRaiseForStatus(403, '', $body);
            $this->fail('Expected PlanRequiredException');
        } catch (PlanRequiredException $e) {
            $this->assertSame('business', $e->getRequiredPlan());
        }
    }

    public function testStatus403FallbackThrowsAuthenticationException(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->callRaiseForStatus(403, '', '{}');
    }

    public function testStatus402ThrowsPlanRequiredException(): void
    {
        $this->expectException(PlanRequiredException::class);
        $body = json_encode(['required_plan' => 'pro']);
        $this->callRaiseForStatus(402, '', $body);
    }

    public function testStatus402DefaultsToPro(): void
    {
        try {
            $this->callRaiseForStatus(402, '', '{}');
            $this->fail('Expected PlanRequiredException');
        } catch (PlanRequiredException $e) {
            $this->assertSame('pro', $e->getRequiredPlan());
        }
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