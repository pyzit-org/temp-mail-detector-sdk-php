<?php

declare(strict_types=1);

namespace Pyzit\TempMail\Models;

final class Signals
{
    public function __construct(
        public readonly array $positive,
        public readonly array $negative,
        public readonly array $neutral,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            positive: (array)($data['positive'] ?? []),
            negative: (array)($data['negative'] ?? []),
            neutral:  (array)($data['neutral']  ?? []),
        );
    }
}