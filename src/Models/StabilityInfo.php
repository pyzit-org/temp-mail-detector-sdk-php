<?php

declare(strict_types=1);

namespace Pyzit\TempMail\Models;

final class StabilityInfo
{
    public function __construct(
        public readonly float $stabilityScore,
        public readonly int $domainAgeDays,
        public readonly bool $isNewDomain,
        public readonly array $riskFactors,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            stabilityScore: (float)(($data['stability_score'] ?? 0.0)),
            domainAgeDays:  (int)  (($data['domain_age_days'] ?? 0)),
            isNewDomain:    (bool) (($data['is_new_domain']   ?? false)),
            riskFactors:    (array)(($data['risk_factors']    ?? [])),
        );
    }
}