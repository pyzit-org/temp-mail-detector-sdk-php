<?php

declare(strict_types=1);

namespace Pyzit\TempMail\Models;

/**
 * Result from the /v1/tmd/bulk/ endpoint.
 */
final class BulkResult
{
    /**
     * @param string[] $flaggedDomains
     * @param CheckResult[] $results
     */
    public function __construct(
        public readonly int $total,
        public readonly string $status,
        public readonly int $disposableCount,
        public readonly int $privacyRelayCount,
        public readonly array $flaggedDomains,
        public readonly array $results,
    ) {}

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        $rawResults = (array)($data['results'] ?? []);
        $results = [];
        foreach ($rawResults as $item) {
            $results[] = CheckResult::fromArray((array)$item);
        }

        $total = isset($data['total']) ? (int)$data['total'] : count($results);

        return new self(
            total:               $total,
            status:              (string)($data['status'] ?? 'clean'),
            disposableCount:     (int)($data['disposable_count'] ?? 0),
            privacyRelayCount:   (int)($data['privacy_relay_count'] ?? 0),
            flaggedDomains:      array_values((array)($data['flagged_domains'] ?? [])),
            results:             $results,
        );
    }

    /**
     * Return only the disposable email addresses.
     * @return string[]
     */
    public function disposableEmails(): array
    {
        $emails = [];
        foreach ($this->results as $result) {
            if ($result->isDisposable) {
                $emails[] = $result->email;
            }
        }
        return $emails;
    }

    /**
     * Return only the clean (non-disposable) email addresses.
     * @return string[]
     */
    public function cleanEmails(): array
    {
        $emails = [];
        foreach ($this->results as $result) {
            if ($result->isClean()) {
                $emails[] = $result->email;
            }
        }
        return $emails;
    }

    /**
     * Return only the privacy relay email addresses.
     * @return string[]
     */
    public function privacyRelayEmails(): array
    {
        $emails = [];
        foreach ($this->results as $result) {
            if ($result->isPrivacyRelay) {
                $emails[] = $result->email;
            }
        }
        return $emails;
    }

    /** True when all emails in the batch are clean. */
    public function isAllClean(): bool
    {
        return $this->status === 'clean';
    }

    /** True when all emails in the batch are disposable. */
    public function isAllDisposable(): bool
    {
        return $this->status === 'disposable';
    }

    /** True when the batch contains mixed results. */
    public function isMixed(): bool
    {
        return $this->status === 'mixed';
    }
}
