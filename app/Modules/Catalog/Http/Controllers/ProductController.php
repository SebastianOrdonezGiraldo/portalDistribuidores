<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Product;
use App\Modules\Categories\Queries\CategoryBreadcrumbsQuery;
use App\Modules\Documents\Services\TechSheetDownloadService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function show(
        Request $request,
        Product $product,
        CategoryBreadcrumbsQuery $breadcrumbsQuery,
        TechSheetDownloadService $downloadService,
    ): View {
        $product->loadMissing('category.synonyms', 'photos', 'primaryPhoto', 'videos', 'documents', 'variantAttribute', 'variants.attributeValue.attribute');

        if (! $product->is_active) {
            abort(404);
        }

        $breadcrumbs = $breadcrumbsQuery->execute($product->category);
        $techSheet = $product->documents->firstWhere('type', 'tech_sheet');
        $remainingDownloads = null;
        $commercialSnapshot = $this->buildCommercialSnapshot($product);
        $relatedProducts = $this->relatedProducts($product);
        $alternativeProducts = $this->alternativeProducts(
            $product,
            $relatedProducts->pluck('id')->all(),
        );

        if ($techSheet && $request->user()?->distributor) {
            $remainingDownloads = $downloadService->remainingDownloads(
                $request->user()->distributor,
                $techSheet,
                CarbonImmutable::now(),
            );
        }

        return view('product.show', [
            'product' => $product,
            'breadcrumbs' => $breadcrumbs,
            'techSheet' => $techSheet,
            'remainingDownloads' => $remainingDownloads,
            'commercialSnapshot' => $commercialSnapshot,
            'relatedProducts' => $relatedProducts,
            'alternativeProducts' => $alternativeProducts,
        ]);
    }

    /**
     * @return array{
     *   brand:string,
     *   unit:string,
     *   packaging:string,
     *   presentation:string,
     *   minMultiple:int,
     *   stock:float|null,
     *   leadTimeLabel:string,
     *   etaLabel:string,
     *   promoLabel:string|null,
     *   discountPercent:float|null,
     *   availability:array{key:string,label:string,badge:string,helper:string}
     * }
     */
    private function buildCommercialSnapshot(Product $product): array
    {
        $stockRaw = data_get($product, 'stock');
        $stock = is_numeric($stockRaw) ? max(0, (float) $stockRaw) : null;

        $minMultipleRaw = data_get($product, 'min_multiple')
            ?? data_get($product, 'min_order_qty')
            ?? data_get($product, 'minimum_order_qty')
            ?? 1;

        $minMultiple = is_numeric($minMultipleRaw) && (float) $minMultipleRaw > 0
            ? max(1, (int) ceil((float) $minMultipleRaw))
            : 1;

        $leadTimeRaw = data_get($product, 'lead_time_days') ?? data_get($product, 'eta_days');
        $leadTimeDays = is_numeric($leadTimeRaw) && (int) $leadTimeRaw >= 0
            ? (int) $leadTimeRaw
            : null;

        $discountRaw = data_get($product, 'discount_percent');
        $discountPercent = is_numeric($discountRaw) && (float) $discountRaw > 0
            ? round((float) $discountRaw, 2)
            : null;

        $availability = $this->availabilityStatus(
            $product->is_active,
            $stock,
            $minMultiple,
        );

        return [
            'brand' => (string) (data_get($product, 'brand') ?: 'Marca no especificada'),
            'unit' => (string) (data_get($product, 'unit') ?: data_get($product, 'unit_label') ?: 'unidad'),
            'packaging' => (string) (data_get($product, 'packaging') ?: 'Empaque estandar'),
            'presentation' => (string) (data_get($product, 'presentation') ?: 'Presentacion comercial'),
            'minMultiple' => $minMultiple,
            'stock' => $stock,
            'leadTimeLabel' => $this->leadTimeLabel($leadTimeDays),
            'etaLabel' => (string) (data_get($product, 'eta_label') ?: 'Confirmacion al finalizar el pedido'),
            'promoLabel' => data_get($product, 'promo_label'),
            'discountPercent' => $discountPercent,
            'availability' => $availability,
        ];
    }

    /**
     * @return array{key:string,label:string,badge:string,helper:string}
     */
    private function availabilityStatus(bool $isActive, ?float $stock, int $minMultiple): array
    {
        if (! $isActive) {
            return [
                'key' => 'inactive',
                'label' => 'No disponible',
                'badge' => 'danger',
                'helper' => 'Producto inactivo temporalmente para distribuidores.',
            ];
        }

        if ($stock === null) {
            return [
                'key' => 'check',
                'label' => 'Disponibilidad a confirmar',
                'badge' => 'warning',
                'helper' => 'Consulta inventario actualizado antes de confirmar.',
            ];
        }

        if ($stock <= 0.0) {
            return [
                'key' => 'out',
                'label' => 'Agotado',
                'badge' => 'danger',
                'helper' => 'Sin stock inmediato. Revisa alternativas sugeridas.',
            ];
        }

        if ($stock <= max(1.0, $minMultiple * 2)) {
            return [
                'key' => 'low',
                'label' => 'Stock limitado',
                'badge' => 'warning',
                'helper' => 'Quedan pocas unidades disponibles.',
            ];
        }

        return [
            'key' => 'in',
            'label' => 'Disponible',
            'badge' => 'success',
            'helper' => 'Inventario disponible para despacho.',
        ];
    }

    private function leadTimeLabel(?int $leadTimeDays): string
    {
        if ($leadTimeDays === null) {
            return 'Tiempo de entrega a confirmar';
        }

        if ($leadTimeDays === 0) {
            return 'Despacho inmediato';
        }

        if ($leadTimeDays === 1) {
            return 'Entrega estimada en 1 dia';
        }

        return "Entrega estimada en {$leadTimeDays} dias";
    }

    private function relatedProducts(Product $product): EloquentCollection
    {
        if (! $product->category_id) {
            return new EloquentCollection();
        }

        return Product::query()
            ->where('is_active', true)
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->with(['category', 'primaryPhoto', 'variantAttribute', 'variants.attributeValue'])
            ->latest('id')
            ->limit(8)
            ->get();
    }

    /**
     * @param array<int, int|string> $excludeIds
     */
    private function alternativeProducts(Product $product, array $excludeIds = []): EloquentCollection
    {
        $excludeIds[] = $product->id;

        $query = Product::query()
            ->where('is_active', true)
            ->whereNotIn('id', $excludeIds)
            ->with(['category', 'primaryPhoto', 'variantAttribute', 'variants.attributeValue'])
            ->latest('id')
            ->limit(8);

        if ($product->category_id) {
            $query->where(function ($builder) use ($product) {
                $builder
                    ->whereNull('category_id')
                    ->orWhere('category_id', '!=', $product->category_id);
            });
        }

        return $query->get();
    }
}
