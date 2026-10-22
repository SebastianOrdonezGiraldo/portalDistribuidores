<?php

namespace App\View\Composers;

use App\Modules\Catalog\Enums\CatalogBannerPlacement;
use App\Modules\Catalog\Models\CatalogBanner;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Shared data for layouts.guest: auth banners and support contact.
 */
class GuestLayoutComposer
{
    public function compose(View $view): void
    {
        try {
            $authBanners = Schema::hasTable('catalog_banners')
                ? CatalogBanner::query()
                    ->where('placement', CatalogBannerPlacement::Auth)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get()
                : collect();
        } catch (\Throwable) {
            // Las vistas de autenticación deben poder renderizarse antes de migrar la tabla.
            $authBanners = collect();
        }

        $view->with([
            'authBanners' => $authBanners,
            ...SupportContactData::forView(),
        ]);
    }
}
