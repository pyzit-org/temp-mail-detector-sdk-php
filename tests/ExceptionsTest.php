<?php

declare(strict_types=1);

namespace Pyzit\TempMail\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Pyzit\TempMail\Exceptions\ApiException;
use Pyzit\TempMail\Exceptions\AuthenticationException;
use Pyzit\TempMail\Exceptions\PlanRequiredException;
use Pyzit\TempMail\Exceptions\PyzitException;
use Pyzit\TempMail\Exceptions\RateLimitException;
use Pyzit\TempMail\Exceptions\ScopeException;
use Pyzit\TempMail\Exceptions\TimeoutException;

class ExceptionsTest extends TestCase
{
    // ── PyzitException base ───────────────────────────────────────

    public function testPyzitExceptionIsRuntimeException(): void
    {
        $this->assertInstanceOf(\RuntimeException::class, new PyzitException('test'));
    }

    public function testPyzitExceptionStoresMessage(): void
    {
        $e = new PyzitException('something went wrong');
        $this->assertSame('something went wrong', $e->getMessage());
    }

    // ── all exceptions extend PyzitException ──────────────────────

    #[DataProvider('allExceptionClasses')]
    public function testAllExceptionsExtendPyzitException(string $class): void
    {
        $this->assertInstanceOf(PyzitException::class, new $class());
    }

    #[DataProvider('allExceptionClasses')]
    public function testAllExceptionsAreThrowable(string $class): void
    {
        $this->expectException(PyzitException::class);
        throw new $class();
    }

    public static function allExceptionClasses(): array
    {
        return [
            'AuthenticationException' => [AuthenticationException::class],
            'ScopeException'          => [ScopeException::class],
            'PlanRequiredException'   => [PlanRequiredException::class],
            'RateLimitException'      => [RateLimitException::class],
            'ApiException'            => [ApiException::class],
            'TimeoutException'        => [TimeoutException::class],
        ];
    }

    // ── AuthenticationException ───────────────────────────────────

    public function testAuthenticationExceptionDefaultMessage(): void
    {
        $e = new AuthenticationException();
        $this->assertStringContainsString('Invalid or missing', $e->getMessage());
    }

    public function testAuthenticationExceptionCustomMessage(): void
    {
        $e = new AuthenticationException('Access denied (403).');
        $this->assertSame('Access denied (403).', $e->getMessage());
    }

    // ── ScopeException ────────────────────────────────────────────

    public function testScopeExceptionStoresScope(): void
    {
        $e = new ScopeException('detailed:tempemail_check');
        $this->assertSame('detailed:tempemail_check', $e->getRequiredScope());
    }

    public function testScopeExceptionIncludesScopeInMessage(): void
    {
        $e = new ScopeException('bulk:validate');
        $this->assertStringContainsString('bulk:validate', $e->getMessage());
    }

    public function testScopeExceptionDefaultsToEmptyScope(): void
    {
        $this->assertSame('', (new ScopeException())->getRequiredScope());
    }

    // ── PlanRequiredException ─────────────────────────────────────

    public function testPlanRequiredExceptionStoresPlan(): void
    {
        $e = new PlanRequiredException('business');
        $this->assertSame('business', $e->getRequiredPlan());
    }

    public function testPlanRequiredExceptionIncludesPlanInMessage(): void
    {
        $e = new PlanRequiredException('pro');
        $this->assertStringContainsString('pro', $e->getMessage());
    }

    public function testPlanRequiredExceptionDefaultsToPro(): void
    {
        $this->assertSame('pro', (new PlanRequiredException())->getRequiredPlan());
    }

    // ── RateLimitException ────────────────────────────────────────

    public function testRateLimitExceptionStoresRetryAfter(): void
    {
        $e = new RateLimitException(42);
        $this->assertSame(42, $e->getRetryAfter());
    }

    public function testRateLimitExceptionIncludesSecondsInMessage(): void
    {
        $e = new RateLimitException(30);
        $this->assertStringContainsString('30', $e->getMessage());
    }

    public function testRateLimitExceptionDefaultsTo60(): void
    {
        $this->assertSame(60, (new RateLimitException())->getRetryAfter());
    }

    // ── ApiException ──────────────────────────────────────────────

    public function testApiExceptionStoresStatusCode(): void
    {
        $e = new ApiException(503);
        $this->assertSame(503, $e->getStatusCode());
    }

    public function testApiExceptionStoresFullBody(): void
    {
        $e = new ApiException(500, 'Internal Server Error');
        $this->assertSame('Internal Server Error', $e->getResponseBody());
    }

    public function testApiExceptionIncludesStatusInMessage(): void
    {
        $e = new ApiException(503, 'down');
        $this->assertStringContainsString('503', $e->getMessage());
    }

    public function testApiExceptionTruncatesLongBodyInMessage(): void
    {
        $longBody = str_repeat('x', 500);
        $e = new ApiException(500, $longBody);
        $this->assertLessThan(400, strlen($e->getMessage()));
        $this->assertSame(500, strlen($e->getResponseBody()));
    }

    // ── TimeoutException ──────────────────────────────────────────

    public function testTimeoutExceptionHasDefaultMessage(): void
    {
        $e = new TimeoutException();
        $this->assertStringContainsString('timed out', strtolower($e->getMessage()));
    }

    // ── catch-all pattern ─────────────────────────────────────────

    public function testCatchAllPyzitExceptionCatchesAllErrors(): void
    {
        $exceptions = [
            new AuthenticationException(),
            new ScopeException('x:y'),
            new PlanRequiredException('pro'),
            new RateLimitException(10),
            new ApiException(500, 'err'),
            new TimeoutException(),
        ];

        foreach ($exceptions as $e) {
            $caught = false;
            try {
                throw $e;
            } catch (PyzitException) {
                $caught = true;
            }
            $this->assertTrue($caught, get_class($e) . ' was not caught by PyzitException');
        }
    }
}