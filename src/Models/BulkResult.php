<?php

declare(strict_types=1);

namespace Pyzit\TempMail\Models;

/**
 * Result from the Business /v1/validate/bulk/ endpoint.
 */
final class BulkResult
{
    /**
     * @param array<string,bool> $results   email => is_disposable
     */
    public function __construct(
        public readonly array $results,
        public readonly int $processed,
    ) {}

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        $raw = (array)($data['results'] ?? []);
        $results = [];
        foreach ($raw as $email => $isDisposable) {
            $results[(string)$email] = (bool)$isDisposable;
        }

        return new self(
            results:   $results,
            processed: (int)($data['processed'] ?? count($results)),
        );
    }

    /**
     * Return only the disposable email addresses.
     * @return string[]
     */
    public function disposableEmails(): array
    {
        return array_keys(array_filter($this->results, fn(bool $d) => $d));
    }

    /**
     * Return only the clean (non-disposable) email addresses.
     * @return string[]
     */
    public function cleanEmails(): array
    {
        return array_keys(array_filter($this->results, fn(bool $d) => !$d));
    }
}