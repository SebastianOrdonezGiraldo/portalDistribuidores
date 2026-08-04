<?php

namespace App\Modules\Shared\Enums;

enum GoldThresholdBasis: string
{
    case GoldCandidate = 'gold_candidate';
    case SilverCandidate = 'silver_candidate';

    public function label(): string
    {
        return match ($this) {
            self::GoldCandidate => 'Total candidato Oro',
            self::SilverCandidate => 'Total candidato Plata',
        };
    }
}
