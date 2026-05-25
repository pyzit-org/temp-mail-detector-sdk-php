<?php

declare(strict_types=1);

namespace Pyzit\TempMail\Models;

/**
 * Result from the free /v1/validate/check/ endpoint.
 */
final class CheckResult
{
    public function __construct(
        /** The email address that was validated. */
        public readonly string $email,
        /** True if the email is from a disposable/throwaway provider. */
        public readonly bool $isDisposable,
        /** "clean" or "disposable" */
        public readonly string $status,
    ) {}

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            email:        (string)($data['email']         ?? ''),
            isDisposable: (bool)  ($data['is_disposable'] ?? false),
            status:       (string)($data['status']        ?? 'unknown'),
        );
    }

    /** Convenience — true when email is safe to accept. */
    public function isClean(): bool
    {
        return !$this->isDisposable;
    }
}