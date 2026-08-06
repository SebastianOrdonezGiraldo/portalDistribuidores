<?php

namespace App\Modules\Shared\Enums;

enum GoldThresholdBasis: string
{
    case GoldCandidate = 'gold_candidate';
    case SilverCandidate = 'silver_candidate';

    public function label(): string
    {
        return match ($this) {
            self::GoldCandidate => 'Total calculado con precios Oro',
            self::SilverCandidate => 'Total calculado con precios Plata',
        };
    }

    public function historyLabel(): string
    {
        return match ($this) {
            self::GoldCandidate => 'Candidato Oro',
            self::SilverCandidate => 'Candidato Plata',
        };
    }
}
