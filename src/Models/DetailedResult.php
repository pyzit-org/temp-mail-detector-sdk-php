<?php declare(strict_types=1);

namespace Pyzit\TempMail\Models;

/**
 * Result from the /v1/tmd/detailed/ endpoint.
 */
final class DetailedResult
{
    public function __construct(
        public readonly string $email,
        public readonly string $domain,
        public readonly bool $isDisposable,
        public readonly bool $isTrusted,
        public readonly string $classification,
        public readonly float $reputationScore,
        public readonly string $riskLevel,
        public readonly bool $isValidFormat,
        public readonly ?string $ruleApplied,
        public readonly DetailedDetails $details,
        public readonly string $status,
        public readonly string $recommendation,
    ) {}

    /**
     * @param array<string,mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            email: (string) ($data['email'] ?? ''),
            domain: (string) ($data['domain'] ?? ''),
            isDisposable: (bool) ($data['is_disposable'] ?? false),
            isTrusted: (bool) ($data['is_trusted'] ?? false),
            classification: (string) ($data['classification'] ?? 'unknown'),
            reputationScore: (float) ($data['reputation_score'] ?? 0.0),
            riskLevel: strtolower((string) ($data['risk_level'] ?? 'unknown')),
            isValidFormat: (bool) ($data['is_valid_format'] ?? false),
            ruleApplied: ($data['rule_applied'] ?? null) !== null ? (string) $data['rule_applied'] : null,
            details: DetailedDetails::fromArray((array) ($data['details'] ?? [])),
            status: (string) ($data['status'] ?? 'unknown'),
            recommendation: strtolower((string) ($data['recommendation'] ?? 'review')),
        );
    }

    /**
     * True when the email is NOT disposable.
     */
    public function isClean(): bool
    {
        return !$this->isDisposable;
    }

    /**
     * True when the risk level is high.
     */
    public function isHighRisk(): bool
    {
        return $this->riskLevel === 'high';
    }

    /**
     * True when the domain is in the trusted whitelist.
     */
    public function isTrustedDomain(): bool
    {
        return $this->isTrusted;
    }

    /**
     * True when the email should be rejected.
     */
    public function shouldReject(): bool
    {
        return $this->recommendation === 'reject';
    }

    /**
     * True when the email should be challenged (e.g., CAPTCHA).
     */
    public function shouldChallenge(): bool
    {
        return $this->recommendation === 'challenge';
    }

    /**
     * True when the email should be accepted.
     */
    public function shouldAccept(): bool
    {
        return $this->recommendation === 'accept';
    }
}
