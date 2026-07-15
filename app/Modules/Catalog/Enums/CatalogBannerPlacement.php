<?php

namespace App\Modules\Catalog\Enums;

enum CatalogBannerPlacement: string
{
    case Catalog = 'catalog';
    case Auth = 'auth';

    public function label(): string
    {
        return match ($this) {
            self::Catalog => 'Catálogo',
            self::Auth => 'Login y registro',
        };
    }
}
