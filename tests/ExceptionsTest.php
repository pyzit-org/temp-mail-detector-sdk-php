<?php

declare(strict_types=1);

namespace Pyzit\TempMail\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Pyzit\TempMail\Exceptions\ApiException;
use Pyzit\TempMail\Exceptions\AuthenticationException;
use Pyzit\TempMail\Exceptions\InsufficientScopeError;
use Pyzit\TempMail\Exceptions\QuotaExceededError;
use Pyzit\TempMail\Exceptions\BatchLimitExceededError;
use Pyzit\TempMail\Exceptions\PyzitException;
use Pyzit\TempMail\Exceptions\RateLimitException;
use Pyzit\TempMail\Exceptions\ValidationError;
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
            'InsufficientScopeError'  => [InsufficientScopeError::class],
            'QuotaExceededError'      => [QuotaExceededError::class],
            'BatchLimitExceededError' => [BatchLimitExceededError::class],
            'RateLimitException'      => [RateLimitException::class],
            'ValidationError'         => [ValidationError::class],
            'ApiException'            => [ApiException::class],
            'TimeoutException'        => [TimeoutException::class],
        ];
    }

    // ── AuthenticationException ───────────────────────────────────

    public function testAuthenticationExceptionDefaultMessage(): void
    {
        $e = new AuthenticationException();
        $this->assertStringContainsString('Invalid or inactive', $e->getMessage());
    }

    public function testAuthenticationExceptionCustomMessage(): void
    {
        $e = new AuthenticationException('Access denied (403).');
        $this->assertSame('Access denied (403).', $e->getMessage());
    }

    public function testAuthenticationExceptionHasStatusCode(): void
    {
        $e = new AuthenticationException();
        $this->assertSame(401, $e->statusCode);
    }

    // ── InsufficientScopeError ─────────────────────────────────────

    public function testInsufficientScopeErrorStoresScope(): void
    {
        $e = new InsufficientScopeError('tmd:detailed');
        $this->assertSame('tmd:detailed', $e->getRequiredScope());
    }

    public function testInsufficientScopeErrorIncludesScopeInMessage(): void
    {
        $e = new InsufficientScopeError('tmd:basic');
        $this->assertStringContainsString('tmd:basic', $e->getMessage());
    }

    public function testInsufficientScopeErrorDefaultsToEmptyScope(): void
    {
        $this->assertSame('', (new InsufficientScopeError())->getRequiredScope());
    }

    // ── QuotaExceededError ────────────────────────────────────────

    public function testQuotaExceededErrorStoresQuotaType(): void
    {
        $e = new QuotaExceededError('detailed');
        $this->assertSame('detailed', $e->getQuotaType());
    }

    public function testQuotaExceededErrorDefaultsToSimple(): void
    {
        $this->assertSame('simple', (new QuotaExceededError())->getQuotaType());
    }

    // ── BatchLimitExceededError ───────────────────────────────────

    public function testBatchLimitExceededErrorStoresMaxAllowed(): void
    {
        $e = new BatchLimitExceededError(50, 15);
        $this->assertSame(50, $e->getMaxAllowed());
        $this->assertSame(15, $e->getAttempted());
    }

    public function testBatchLimitExceededErrorDefaults(): void
    {
        $e = new BatchLimitExceededError();
        $this->assertSame(10, $e->getMaxAllowed());
        $this->assertSame(0, $e->getAttempted());
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

    public function testRateLimitExceptionHasStatusCode(): void
    {
        $e = new RateLimitException();
        $this->assertSame(429, $e->statusCode);
    }

    // ── ValidationError ────────────────────────────────────────────

    public function testValidationErrorStoresFieldErrors(): void
    {
        $fieldErrors = ['email' => ['Invalid email format']];
        $e = new ValidationError($fieldErrors);
        $this->assertSame($fieldErrors, $e->getFieldErrors());
    }

    public function testValidationErrorDefaultsToEmptyArray(): void
    {
        $this->assertSame([], (new ValidationError())->getFieldErrors());
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
            new InsufficientScopeError('tmd:basic'),
            new QuotaExceededError('simple'),
            new BatchLimitExceededError(10, 5),
            new RateLimitException(10),
            new ValidationError(),
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