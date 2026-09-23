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
        $mxRecords = [];
        if (isset($data['mx_records']) && is_array($data['mx_records'])) {
            foreach ($data['mx_records'] as $r) {
                if (is_array($r)) {
                    $mxRecords[] = MxRecord::fromArray($r);
                } else {
                    // Handle case where mx_records might be simple strings
                    $mxRecords[] = MxRecord::fromArray(['exchange' => (string)$r]);
                }
            }
        }

        return new self(
            hasMx:      (bool)  ($data['has_mx']      ?? false),
            mxRecords:  $mxRecords,
            hasARecord: (bool)  ($data['has_a_record'] ?? false),
            hasSpf:     (bool)  ($data['has_spf']      ?? false),
            hasDmarc:   (bool)  ($data['has_dmarc']    ?? false),
            error: isset($data['error']) && $data['error'] !== null
                       ? (string)$data['error'] : null,
        );
    }
}