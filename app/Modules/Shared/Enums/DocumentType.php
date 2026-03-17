<?php

namespace App\Modules\Shared\Enums;

enum DocumentType: string
{
    case TechSheet = 'tech_sheet';

    public function label(): string
    {
        return match ($this) {
            DocumentType::TechSheet => 'Ficha técnica',
        };
    }
}
