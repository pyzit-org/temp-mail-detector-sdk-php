<?php

declare(strict_types=1);

namespace Pyzit\TempMail\Models;

final class DnsIntelligence
{
    /** @param MxRecord[] $mxRecords */
    public function __construct(
        public readonly bool $hasMx,
        public readonly array $mxRecords,
        public readonly bool $hasARecord,
        public readonly bool $hasSpf,
        public readonly bool $hasDmarc,
        public readonly ?string $error,
    ) {}

    public static function fromArray(array $data): self
    {
        $mx = array_map(
            fn(array $r) => MxRecord::fromArray($r),
            (array)($data['mx_records'] ?? [])
        );

        return new self(
            hasMx:      (bool)  ($data['has_mx']      ?? false),
            mxRecords:  $mx,
            hasARecord: (bool)  ($data['has_a_record'] ?? false),
            hasSpf:     (bool)  ($data['has_spf']      ?? false),
            hasDmarc:   (bool)  ($data['has_dmarc']    ?? false),
            error: isset($data['error']) && $data['error'] !== null
                       ? (string)$data['error'] : null,
        );
    }
}