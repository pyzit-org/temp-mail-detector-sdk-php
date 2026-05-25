<?php

declare(strict_types=1);

namespace Pyzit\TempMail\Models;

final class DetailedDetails
{
    public function __construct(
        public readonly ReputationDetail $reputation,
        public readonly Signals $signals,
        public readonly DnsIntelligence $dnsIntelligence,
        public readonly StabilityInfo $stability,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            reputation:      ReputationDetail::fromArray((array)($data['reputation']       ?? [])),
            signals:         Signals::fromArray(         (array)($data['signals']          ?? [])),
            dnsIntelligence: DnsIntelligence::fromArray( (array)($data['dns_intelligence'] ?? [])),
            stability:       StabilityInfo::fromArray(   (array)($data['stability']        ?? [])),
        );
    }
}