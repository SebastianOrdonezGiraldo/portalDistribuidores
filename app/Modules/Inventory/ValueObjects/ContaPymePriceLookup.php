<?php

namespace App\Modules\Inventory\ValueObjects;

final readonly class ContaPymePriceLookup
{
    public function __construct(
        public string $status,
        public ?string $price = null,
        public ?string $message = null,
    ) {}

    public static function ok(string $price): self
    {
        return new self('ok', $price);
    }

    public static function missing(string $message): self
    {
        return new self('missing_contapyme', null, $message);
    }

    public static function error(string $message): self
    {
        return new self('error', null, $message);
    }
}
