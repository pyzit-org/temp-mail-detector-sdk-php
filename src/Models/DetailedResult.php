<?php declare(strict_types=1);

namespace Pyzit\TempMail\Models;

/**
 * Result from the Pro /v1/validate/detailed/ endpoint.
 */
final class DetailedResult
{
    public function __construct(
        public readonly string $email,
        public readonly string $domain,
        public readonly bool $isDisposable,
        public readonly string $status,
        public readonly float $reputationScore,

        /**
         * "low", "medium", or "high"
         */
        public readonly string $riskLevel,

        /**
         * "accept", "review", "challenge", or "reject" — always lowercase
         */
        public readonly string $recommendation,
        public readonly DetailedDetails $details,
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
            status: (string) ($data['status'] ?? 'unknown'),
            reputationScore: (float) ($data['reputation_score'] ?? 0.0),
            riskLevel: (string) (strtolower($data['risk_level'] ?? 'unknown')),
            recommendation: (string) (strtolower($data['recommendation'] ?? 'review')),
            details: DetailedDetails::fromArray((array) ($data['details'] ?? [])),
        );
    }

    /**
     * True when the API says to outright block this email.
     * recommendation === "reject"
     */
    public function shouldReject(): bool
    {
        return $this->recommendation === 'reject';
    }

    /**
     * True when the API recommends extra verification (CAPTCHA, OTP, etc).
     * recommendation === "challenge"
     */
    public function shouldChallenge(): bool
    {
        return $this->recommendation === 'challenge';
    }

    /**
     * True when the API recommends accepting the email without friction.
     * recommendation === "accept"
     */
    public function shouldAccept(): bool
    {
        return $this->recommendation === 'accept';
    }
}
