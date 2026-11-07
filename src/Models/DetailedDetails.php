<?php

declare(strict_types=1);

namespace Pyzit\TempMail\Models;

/**
 * DNS and MX verification intelligence from detailed check.
 */
final class DetailedDetails
{
    /**
     * @param string[] $mxRecords
     * @param string[] $aRecords
     */
    public function __construct(
        public readonly bool $hasMx,
        public readonly array $mxRecords = [],
        public readonly bool $hasA = false,
        public readonly array $aRecords = [],
        public readonly bool $hasSpf = false,
        public readonly ?string $spfRecord = null,
        public readonly bool $hasDmarc = false,
        public readonly ?string $dmarcRecord = null,
    ) {}

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            hasMx:       (bool)($data['has_mx'] ?? false),
            mxRecords:   array_values((array)($data['mx_records'] ?? [])),
            hasA:        (bool)($data['has_a'] ?? false),
            aRecords:    array_values((array)($data['a_records'] ?? [])),
            hasSpf:      (bool)($data['has_spf'] ?? false),
            spfRecord:   isset($data['spf_record']) ? (string)$data['spf_record'] : null,
            hasDmarc:    (bool)($data['has_dmarc'] ?? false),
            dmarcRecord: isset($data['dmarc_record']) ? (string)$data['dmarc_record'] : null,
        );
    }
}
