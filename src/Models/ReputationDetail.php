<?php

declare(strict_types=1);

namespace Pyzit\TempMail\Models;

final class ReputationDetail
{
    public function __construct(
        public readonly float $reputationScore,
        public readonly bool $isDisposable,
        public readonly float $disposableConfidence,
        public readonly string $riskLevel,
        public readonly string $recommendation,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            reputationScore:      (float) ($data['reputation_score']      ?? 0.0),
            isDisposable:         (bool)  ($data['is_disposable']         ?? false),
            disposableConfidence: (float) ($data['disposable_confidence'] ?? 0.0),
            riskLevel:            (string)($data['risk_level']            ?? 'unknown'),
            recommendation:       (string)($data['recommendation']        ?? 'review'),
        );
    }
}