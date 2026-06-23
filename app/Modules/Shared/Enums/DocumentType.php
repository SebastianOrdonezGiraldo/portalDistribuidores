<?php

namespace App\Modules\Shared\Enums;

enum DocumentType: string
{
    case TechSheet = 'tech_sheet';
    case Manual = 'manual';
    case Invima = 'invima';
    case QuickGuide = 'quick_guide';

    public function label(): string
    {
        return match ($this) {
            DocumentType::TechSheet => 'Ficha técnica',
            DocumentType::Manual => 'Manual de usuario',
            DocumentType::Invima => 'INVIMA',
            DocumentType::QuickGuide => 'Guía rápida del producto',
        };
    }

    /**
     * @return list<string>
     */
    public static function protectedValues(): array
    {
        return [
            self::TechSheet->value,
            self::Manual->value,
            self::Invima->value,
            self::QuickGuide->value,
        ];
    }
}
