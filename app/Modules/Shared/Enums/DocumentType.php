<?php

namespace App\Modules\Shared\Enums;

/**
 * Product document categories exposed by catalog/admin download flows.
 *
 * The protected values list is the shared source of truth for document types
 * that must use protected storage, authorization and download routing.
 */
enum DocumentType: string
{
    case TechSheet = 'tech_sheet';
    case Manual = 'manual';
    case Invima = 'invima';
    case QuickGuide = 'quick_guide';
    case CalibrationDocument = 'calibration_document';

    /**
     * Human label used in admin forms, catalog views and download messages.
     */
    public function label(): string
    {
        return match ($this) {
            DocumentType::TechSheet => 'Ficha técnica',
            DocumentType::Manual => 'Manual de usuario',
            DocumentType::Invima => 'INVIMA',
            DocumentType::QuickGuide => 'Guía rápida del producto',
            DocumentType::CalibrationDocument => 'Documento de calibracion',
        };
    }

    /**
     * Values that must be routed through protected document handling.
     *
     * @return list<string>
     */
    public static function protectedValues(): array
    {
        return [
            self::TechSheet->value,
            self::Manual->value,
            self::Invima->value,
            self::QuickGuide->value,
            self::CalibrationDocument->value,
        ];
    }
}
