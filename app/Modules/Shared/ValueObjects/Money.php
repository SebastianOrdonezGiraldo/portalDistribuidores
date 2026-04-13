<?php

namespace App\Modules\Shared\ValueObjects;

final readonly class Money
{
    public function __construct(public float $amount) {}

    public function format(): string
    {
        return number_format($this->amount, 2, ',', '.');
    }
}
