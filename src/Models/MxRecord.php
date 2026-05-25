<?php

declare(strict_types=1);

namespace Pyzit\TempMail\Models;

final class MxRecord
{
    public function __construct(
        public readonly int $priority,
        public readonly string $exchange,
        public readonly array $ips,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            priority: (int)   ($data['priority'] ?? 0),
            exchange: (string)($data['exchange'] ?? ''),
            ips:      (array) ($data['ips']      ?? []),
        );
    }
}