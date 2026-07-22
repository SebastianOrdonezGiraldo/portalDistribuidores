<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Actions\UploadCatalogBannerAction;
use App\Modules\Catalog\Enums\CatalogBannerPlacement;
use App\Modules\Catalog\Models\CatalogBanner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CatalogBannerAdminController extends Controller
{
    private const MAX_FILE_SIZE_KB = 5120;

    private const MAX_BANNERS = 8;

    public function index(): View
    {
        return view('admin.catalog-banners.index', [
            'banners' => CatalogBanner::query()
                ->where('placement', CatalogBannerPlacement::Auth)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
            'maxFileSizeKb' => self::MAX_FILE_SIZE_KB,
            'maxBanners' => self::MAX_BANNERS,
            'recommendedDimensions' => '1600 × 480 px',
        ]);
    }

    public function store(Request $request, UploadCatalogBannerAction $uploadBannerAction): RedirectResponse
    {
        $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:'.self::MAX_FILE_SIZE_KB],
        ]);

        $placement = CatalogBannerPlacement::Auth;

        if (CatalogBanner::query()->where('placement', $placement)->count() >= self::MAX_BANNERS) {
            return back()->withErrors(['image' => 'Puedes cargar máximo '.self::MAX_BANNERS.' banners de login.']);
        }

        $uploadBannerAction->execute(
            $request->file('image'),
            trim((string) $request->string('title')),
            $placement,
            (int) (CatalogBanner::query()->where('placement', $placement)->max('sort_order') ?? -1) + 1,
        );

        return back()->with('status', 'Banner de login agregado al carrusel.');
    }

    public function destroy(CatalogBanner $catalogBanner): RedirectResponse
    {
        $path = $catalogBanner->path;
        $catalogBanner->delete();

        if ($path !== '' && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        return back()->with('status', 'Banner de login eliminado.');
    }
}
