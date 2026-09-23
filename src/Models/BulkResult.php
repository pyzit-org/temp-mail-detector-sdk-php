<?php

declare(strict_types=1);

namespace Pyzit\TempMail\Models;

use Pyzit\TempMail\Models\CheckResult;

/**
 * Result from the /v1/tmd/bulk/ endpoint.
 */
final class BulkResult
{
    /**
     * @param array<string,bool> $results
     */
    public function __construct(
        public readonly int $processed,
        public readonly string $status,
        public readonly int $disposableCount,
        public readonly array $flaggedDomains,
        public readonly array $results,
    ) {}

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        $rawResults = (array)($data['results'] ?? []);
        $results = [];
        foreach ($rawResults as $item) {
            $email = (string)($item['email'] ?? '');
            $isDisposable = (bool)($item['is_disposable'] ?? false);
            $results[$email] = $isDisposable;
        }

        return new self(
            processed: (int)($data['processed'] ?? count($results)),
            status: (string)($data['status'] ?? 'unknown'),
            disposableCount: (int)($data['disposable_count'] ?? 0),
            flaggedDomains: (array)($data['flagged_domains'] ?? []),
            results: $results,
        );
    }

    /**
     * Return only the disposable email addresses.
     * @return string[]
     */
    public function disposableEmails(): array
    {
        return array_keys(array_filter($this->results, fn(bool $isDisposable) => $isDisposable));
    }

    /**
     * Return only the clean (non-disposable) email addresses.
     * @return string[]
     */
    public function cleanEmails(): array
    {
        return array_keys(array_filter($this->results, fn(bool $isDisposable) => !$isDisposable));
    }

    /**
     * True when all emails in the batch are clean.
     */
    public function isAllClean(): bool
    {
        return $this->status === 'clean';
    }

    /**
     * True when all emails in the batch are disposable.
     */
    public function isAllDisposable(): bool
    {
        return $this->status === 'disposable';
    }

    /**
     * True when the batch contains both clean and disposable emails.
     */
    public function isMixed(): bool
    {
        return $this->status === 'mixed';
    }
}