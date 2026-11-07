<?php

declare(strict_types=1);

namespace Pyzit\TempMail\Tests;

use PHPUnit\Framework\TestCase;
use Pyzit\TempMail\HttpClient;
use Pyzit\TempMail\TempMailClient;
use Pyzit\TempMail\Models\CheckResult;
use Pyzit\TempMail\Models\DetailedResult;
use Pyzit\TempMail\Models\BulkResult;
use Pyzit\TempMail\Exceptions\AuthenticationException;
use Pyzit\TempMail\Exceptions\InsufficientScopeError;
use Pyzit\TempMail\Exceptions\QuotaExceededError;
use Pyzit\TempMail\Exceptions\BatchLimitExceededError;
use Pyzit\TempMail\Exceptions\RateLimitException;
use Pyzit\TempMail\Exceptions\ApiException;
use Pyzit\TempMail\Exceptions\TimeoutException;
use Pyzit\TempMail\Exceptions\PyzitException;

/**
 * We test TempMailClient by injecting a stub HttpClient that bypasses cURL.
 * StubHttpClient is defined at the bottom of this file.
 */
class TempMailClientTest extends TestCase
{
    private function makeClient(array $responses): TempMailClient
    {
        $stub   = new StubHttpClient($responses);
        $client = new TempMailClient(Fixtures::FAKE_TOKEN);
        // Inject stub via reflection
        $ref = new \ReflectionProperty(TempMailClient::class, 'http');
        $ref->setValue($client, $stub);
        return $client;
    }

    // ── check() ───────────────────────────────────────────────────

    public function testCheckReturnsCheckResult(): void
    {
        $client = $this->makeClient([Fixtures::cleanPayload()]);
        $r = $client->check('hi@pyzit.com');
        $this->assertInstanceOf(CheckResult::class, $r);
        $this->assertSame('hi@pyzit.com', $r->email);
        $this->assertFalse($r->isDisposable);
        $this->assertSame('clean', $r->status);
    }

    public function testCheckDisposableEmail(): void
    {
        $client = $this->makeClient([Fixtures::disposablePayload()]);
        $r = $client->check('user@mailnator.com');
        $this->assertTrue($r->isDisposable);
        $this->assertSame('disposable', $r->status);
    }

    public function testCheckCallsCorrectPath(): void
    {
        $stub   = new StubHttpClient([Fixtures::cleanPayload()]);
        $client = new TempMailClient(Fixtures::FAKE_TOKEN);
        $ref = new \ReflectionProperty(TempMailClient::class, 'http');
        $ref->setValue($client, $stub);

        $client->check('hi@pyzit.com');
        $this->assertSame('/v1/tmd/check/', $stub->lastPath);
        $this->assertSame(['email' => 'hi@pyzit.com'], $stub->lastBody);
    }

    // ── detailed() ────────────────────────────────────────────────

    public function testDetailedReturnsDetailedResult(): void
    {
        $client = $this->makeClient([Fixtures::detailedPayload()]);
        $r = $client->detailed('x@new-domain.io');
        $this->assertInstanceOf(DetailedResult::class, $r);
        $this->assertSame('x@new-domain.io', $r->email);
        $this->assertTrue($r->isDisposable);
        $this->assertSame('high', $r->riskLevel);
        $this->assertTrue($r->isHighRisk());
    }

    public function testDetailedCallsCorrectPath(): void
    {
        $stub   = new StubHttpClient([Fixtures::detailedPayload()]);
        $client = new TempMailClient(Fixtures::FAKE_TOKEN);
        $ref = new \ReflectionProperty(TempMailClient::class, 'http');
        $ref->setValue($client, $stub);

        $client->detailed('x@new-domain.io');
        $this->assertSame('/v1/tmd/detailed/', $stub->lastPath);
        $this->assertSame(['email' => 'x@new-domain.io'], $stub->lastBody);
    }

    public function testDetailedMxRecords(): void
    {
        $client = $this->makeClient([Fixtures::detailedWithMxPayload()]);
        $r      = $client->detailed('hi@pyzit.com');
        $this->assertTrue($r->hasMx());
        $this->assertTrue($r->details->hasMx);
        $this->assertSame('mail1.pyzit.com', $r->details->mxRecords[0]);
    }

    // ── bulk() ────────────────────────────────────────────────────

    public function testBulkReturnsBulkResult(): void
    {
        $client = $this->makeClient([Fixtures::bulkPayload()]);
        $r = $client->bulk(['hi@pyzit.com', 'x@mailnator.com']);
        $this->assertInstanceOf(BulkResult::class, $r);
        $this->assertSame(4, $r->total);
        $this->assertSame('mixed', $r->status);
        $this->assertSame(2, $r->disposableCount);
        $this->assertSame(0, $r->privacyRelayCount);
        $this->assertContains('mailnator.com', $r->flaggedDomains);
    }

    public function testBulkCallsCorrectPath(): void
    {
        $stub   = new StubHttpClient([Fixtures::bulkPayload()]);
        $client = new TempMailClient(Fixtures::FAKE_TOKEN);
        $ref = new \ReflectionProperty(TempMailClient::class, 'http');
        $ref->setAccessible(true);
        $ref->setValue($client, $stub);

        $emails = ['hi@pyzit.com', 'x@mailnator.com'];
        $client->bulk($emails);
        $this->assertSame('/v1/tmd/bulk/', $stub->lastPath);
        $this->assertSame(['emails' => $emails], $stub->lastBody);
    }

    public function testBulkDisposableAndCleanHelpers(): void
    {
        $client = $this->makeClient([Fixtures::bulkPayload()]);
        $r      = $client->bulk([]);
        $this->assertContains('x@mailnator.com',    $r->disposableEmails());
        $this->assertContains('fake@temp-mail.org', $r->disposableEmails());
        $this->assertContains('hi@pyzit.com',       $r->cleanEmails());
        $this->assertContains('support@github.com', $r->cleanEmails());
    }

    // ── error propagation ─────────────────────────────────────────

    public function testCheckPropagatesAuthenticationException(): void
    {
        $this->expectException(AuthenticationException::class);
        $stub   = new StubHttpClient([], new AuthenticationException());
        $client = new TempMailClient(Fixtures::FAKE_TOKEN);
        $ref = new \ReflectionProperty(TempMailClient::class, 'http');
        $ref->setAccessible(true);
        $ref->setValue($client, $stub);
        $client->check('x@y.com');
    }

    public function testCheckPropagatesInsufficientScopeError(): void
    {
        $this->expectException(InsufficientScopeError::class);
        $stub   = new StubHttpClient([], new InsufficientScopeError('tmd:basic'));
        $client = new TempMailClient(Fixtures::FAKE_TOKEN);
        $ref = new \ReflectionProperty(TempMailClient::class, 'http');
        $ref->setAccessible(true);
        $ref->setValue($client, $stub);
        $client->check('x@y.com');
    }

    public function testDetailedPropagatesQuotaExceededError(): void
    {
        $this->expectException(QuotaExceededError::class);
        $stub   = new StubHttpClient([], new QuotaExceededError('detailed'));
        $client = new TempMailClient(Fixtures::FAKE_TOKEN);
        $ref = new \ReflectionProperty(TempMailClient::class, 'http');
        $ref->setAccessible(true);
        $ref->setValue($client, $stub);
        $client->detailed('x@y.com');
    }

    public function testBulkPropagatesBatchLimitExceededError(): void
    {
        $this->expectException(BatchLimitExceededError::class);
        $stub   = new StubHttpClient([], new BatchLimitExceededError(10, 15));
        $client = new TempMailClient(Fixtures::FAKE_TOKEN);
        $ref = new \ReflectionProperty(TempMailClient::class, 'http');
        $ref->setAccessible(true);
        $ref->setValue($client, $stub);
        $client->bulk(['x@y.com']);
    }

    public function testCheckPropagatesRateLimitException(): void
    {
        $this->expectException(RateLimitException::class);
        $stub   = new StubHttpClient([], new RateLimitException(45));
        $client = new TempMailClient(Fixtures::FAKE_TOKEN);
        $ref = new \ReflectionProperty(TempMailClient::class, 'http');
        $ref->setAccessible(true);
        $ref->setValue($client, $stub);
        $client->check('x@y.com');
    }

    public function testCheckPropagatesApiException(): void
    {
        $this->expectException(ApiException::class);
        $stub   = new StubHttpClient([], new ApiException(500, 'error'));
        $client = new TempMailClient(Fixtures::FAKE_TOKEN);
        $ref = new \ReflectionProperty(TempMailClient::class, 'http');
        $ref->setAccessible(true);
        $ref->setValue($client, $stub);
        $client->check('x@y.com');
    }

    public function testCheckPropagatesTimeoutException(): void
    {
        $this->expectException(TimeoutException::class);
        $stub   = new StubHttpClient([], new TimeoutException());
        $client = new TempMailClient(Fixtures::FAKE_TOKEN);
        $ref = new \ReflectionProperty(TempMailClient::class, 'http');
        $ref->setAccessible(true);
        $ref->setValue($client, $stub);
        $client->check('x@y.com');
    }

    public function testAllErrorsArePyzitException(): void
    {
        $errors = [
            new AuthenticationException(),
            new InsufficientScopeError('tmd:basic'),
            new QuotaExceededError('simple'),
            new BatchLimitExceededError(10, 5),
            new RateLimitException(10),
            new ApiException(500, 'err'),
            new TimeoutException(),
        ];

        foreach ($errors as $error) {
            $stub   = new StubHttpClient([], $error);
            $client = new TempMailClient(Fixtures::FAKE_TOKEN);
            $ref = new \ReflectionProperty(TempMailClient::class, 'http');
            $ref->setAccessible(true);
            $ref->setValue($client, $stub);

            try {
                $client->check('x@y.com');
                $this->fail('Expected exception');
            } catch (PyzitException $e) {
                $this->assertInstanceOf(PyzitException::class, $e);
            }
        }
    }
}

// ── StubHttpClient ────────────────────────────────────────────────

/**
 * In-memory HttpClient replacement — no cURL, no network.
 * Returns preset responses or throws a preset exception.
 */
class StubHttpClient extends HttpClient
{
    public string $lastPath = '';
    public array  $lastBody = [];
    private int   $callIndex = 0;

    public function __construct(
        private array $responses = [],
        private ?\Throwable $throw = null,
    ) {
        // Skip parent constructor — we never use cURL
    }

    public function post(string $path, array $body): array
    {
        $this->lastPath = $path;
        $this->lastBody = $body;

        if ($this->throw !== null) {
            throw $this->throw;
        }

        $response = $this->responses[$this->callIndex] ?? [];
        $this->callIndex++;
        return $response;
    }
}