<?php

namespace App\Modules\Catalog\Enums;

enum CatalogBannerPlacement: string
{
    case Auth = 'auth';

    public function label(): string
    {
        return match ($this) {
            self::Auth => 'Login y registro',
        };
    }
}
