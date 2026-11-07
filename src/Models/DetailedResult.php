<?php

declare(strict_types=1);

namespace Pyzit\TempMail\Models;

/**
 * Result from the /v1/tmd/detailed/ endpoint.
 */
final class DetailedResult
{
    /**
     * @param array<string,mixed>|null $ruleDetails
     */
    public function __construct(
        public readonly string $email,
        public readonly string $domain,
        public readonly bool $isDisposable,
        public readonly bool $isTrusted,
        public readonly string $classification,
        public readonly int $reputationScore,
        public readonly string $riskLevel,
        public readonly bool $isValidFormat,
        public readonly DetailedDetails $details,
        public readonly bool $isPrivacyRelay = false,
        public readonly ?string $relayProvider = null,
        public readonly ?string $providerName = null,
        public readonly bool $cached = false,
        public readonly ?string $ruleApplied = null,
        public readonly ?array $ruleDetails = null,
        public readonly string $status = 'clean',
    ) {}

    /**
     * @param array<string,mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $isDisposable = (bool)($data['is_disposable'] ?? false);
        $isPrivacyRelay = (bool)($data['is_privacy_relay'] ?? false);
        $status = (string)($data['status'] ?? ($isPrivacyRelay ? 'privacy_relay' : ($isDisposable ? 'disposable' : 'clean')));

        return new self(
            email:           (string)($data['email'] ?? ''),
            domain:          (string)($data['domain'] ?? ''),
            isDisposable:    $isDisposable,
            isTrusted:       (bool)($data['is_trusted'] ?? false),
            classification:  (string)($data['classification'] ?? 'unknown'),
            reputationScore: (int)($data['reputation_score'] ?? 0),
            riskLevel:       strtolower((string)($data['risk_level'] ?? 'low')),
            isValidFormat:   (bool)($data['is_valid_format'] ?? true),
            details:         DetailedDetails::fromArray((array)($data['details'] ?? [])),
            isPrivacyRelay:  $isPrivacyRelay,
            relayProvider:   isset($data['relay_provider']) ? (string)$data['relay_provider'] : null,
            providerName:    isset($data['provider_name']) ? (string)$data['provider_name'] : null,
            cached:          (bool)($data['cached'] ?? false),
            ruleApplied:     isset($data['rule_applied']) ? (string)$data['rule_applied'] : null,
            ruleDetails:     isset($data['rule_details']) && is_array($data['rule_details']) ? $data['rule_details'] : null,
            status:          $status,
        );
    }

    /** Shorthand: True when the email is NOT disposable. */
    public function isClean(): bool
    {
        return !$this->isDisposable;
    }

    /** Shorthand: True when risk level is high. */
    public function isHighRisk(): bool
    {
        return $this->riskLevel === 'high';
    }

    /** Shorthand: True when the domain is trusted. */
    public function isTrustedDomain(): bool
    {
        return $this->isTrusted;
    }

    /** True when the email should be rejected (disposable or untrusted). */
    public function shouldReject(): bool
    {
        return $this->isDisposable || !$this->isTrusted;
    }

    /** True when the email needs manual review. */
    public function shouldFlag(): bool
    {
        return $this->classification === 'unknown' || $this->classification === 'suspicious';
    }

    /** True if active MX records were resolved. */
    public function hasMx(): bool
    {
        return $this->details->hasMx;
    }

    /** Return exchange hostnames. */
    public function mxHostnames(): array
    {
        return $this->details->mxRecords;
    }

    /** Return resolved A record IP addresses. */
    public function aIps(): array
    {
        return $this->details->aRecords;
    }
}
