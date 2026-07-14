<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Product;
use App\Modules\Categories\Queries\CategoryBreadcrumbsQuery;
use App\Modules\Documents\Services\TechSheetDownloadService;
use App\Modules\Orders\Support\OrderLineVat;
use App\Modules\Shared\Enums\DocumentType;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Ver detalle de producto.
     *
     * Devuelve la ficha comercial del producto o listas parciales AJAX de relacionados/alternativas.
     *
     * @group Catalogo publico
     *
     * @unauthenticated
     *
     * @urlParam product integer required ID del producto activo. Example: 10
     *
     * @queryParam list string Lista AJAX a cargar: related o alternatives. Example: related
     * @queryParam related_page integer Pagina de relacionados. Example: 1
     * @queryParam alternatives_page integer Pagina de alternativas. Example: 1
     *
     * @response 200 {"content":"Vista HTML de producto o payload JSON AJAX"}
     * @response 404 {"message":"Producto no encontrado o inactivo"}
     * @response 422 {"message":"Parametros invalidos"}
     */
    public function show(
        Request $request,
        Product $product,
        CategoryBreadcrumbsQuery $breadcrumbsQuery,
        TechSheetDownloadService $downloadService,
    ): View|JsonResponse {
        $queryState = $this->resolveQueryState($request);

        $product->loadMissing('category.synonyms', 'photos', 'primaryPhoto', 'videos', 'documents', 'variantAttribute', 'variants.attributeValue.attribute');

        if (! $product->is_active) {
            abort(404);
        }

        $breadcrumbs = $breadcrumbsQuery->execute($product->category);
        $techSheet = $product->documents->firstWhere('type', DocumentType::TechSheet->value);
        $remainingDownloads = null;
        $techSheetMonthlyLimit = $downloadService->monthlyLimit();
        $commercialSnapshot = $this->buildCommercialSnapshot($product);
        $relatedProducts = $this->relatedProducts($product, $queryState);
        $alternativeProducts = $this->alternativeProducts($product, $queryState);

        if ($techSheet && $request->user()?->distributor) {
            $remainingDownloads = $downloadService->remainingDownloads(
                $request->user()->distributor,
                $techSheet,
                CarbonImmutable::now(),
            );
        }

        $viewData = $this->buildViewData($product, $commercialSnapshot, $techSheet);

        if ($request->ajax()) {
            return match ((string) ($queryState['list'] ?? '')) {
                'related' => response()->json($this->buildProductListPayload($product, $relatedProducts, 'related', $queryState)),
                'alternatives' => response()->json($this->buildProductListPayload($product, $alternativeProducts, 'alternatives', $queryState)),
                default => abort(404),
            };
        }

        return view('product.show', array_merge($viewData, [
            'product' => $product,
            'breadcrumbs' => $breadcrumbs,
            'techSheet' => $techSheet,
            'techSheetMonthlyLimit' => $techSheetMonthlyLimit,
            'remainingDownloads' => $remainingDownloads,
            'relatedProducts' => $relatedProducts,
            'alternativeProducts' => $alternativeProducts,
            'canonicalUrl' => route('products.show', $product),
        ]));
    }

    /**
     * Prepares all display variables so that the Blade view contains no business logic.
     */
    private function buildViewData(Product $product, array $commercial, mixed $techSheet): array
    {
        $formatQty = static fn (float|int $value): string => rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');

        $mainPhoto = $product->primaryPhoto ?? $product->photos->first();
        $galleryPhotos = $product->photos->take(10);
        $categoryName = $product->category?->name ?? 'Sin categoría';
        $brand = $commercial['brand'] ?? 'Marca no especificada';
        $unitLabel = $commercial['unit'] ?? 'unidades';
        $unitLabelLower = Str::lower($unitLabel);
        $leadTimeLabel = $commercial['leadTimeLabel'] ?? null;
        $etaLabel = $commercial['etaLabel'] ?? null;
        $minMultiple = max(1, (int) ceil((float) ($commercial['minMultiple'] ?? 1)));
        $stepValue = (string) $minMultiple;
        $discountPercent = $commercial['discountPercent'] ?? null;
        $promoLabel = $commercial['promoLabel'] ?? null;
        $stockSyncedAt = $commercial['stockSyncedAt'] ?? null;
        $stockIsStale = (bool) ($commercial['stockIsStale'] ?? true);
        $stockFreshnessLabel = $commercial['stockFreshnessLabel'] ?? 'Aún no sincronizado';

        $activeVariants = $product->activeVariantsCollection();
        $hasVariants = $activeVariants->isNotEmpty();
        $variantAttributeName = $product->variantAttribute?->name ?? 'Variante';
        $minVariantPrice = $hasVariants ? (float) ($activeVariants->min('price') ?? 0) : null;
        $maxVariantPrice = $hasVariants ? (float) ($activeVariants->max('price') ?? 0) : null;
        $price = $hasVariants ? (float) ($minVariantPrice ?? 0) : (float) $product->price;
        $isRangePrice = $hasVariants && $maxVariantPrice !== null && $maxVariantPrice > $price;
        $formattedPrice = $isRangePrice
            ? '$'.number_format($price, 0, ',', '.').' – $'.number_format((float) $maxVariantPrice, 0, ',', '.')
            : '$'.number_format($price, 0, ',', '.');
        $vatLabel = OrderLineVat::label((bool) $product->is_vat_excluded);

        $stock = $hasVariants ? null : ($commercial['stock'] ?? null);
        $canBuy = $hasVariants
            ? $activeVariants->isNotEmpty()
            : ! in_array($commercial['availability']['key'] ?? '', ['out', 'inactive'], true);
        $isLowStock = ! $hasVariants && in_array($commercial['availability']['key'] ?? '', ['low', 'out'], true);

        // Resolve final availability: override to "requires selection" when product has variants.
        $availability = $hasVariants
            ? [
                'key' => 'variant',
                'label' => 'Requiere selección',
                'badge' => 'brand',
                'helper' => 'Selecciona '.Str::lower($variantAttributeName).' para definir precio y disponibilidad.',
            ]
            : ($commercial['availability'] ?? [
                'key' => 'check',
                'label' => 'Disponibilidad a confirmar',
                'badge' => 'warning',
                'helper' => 'Consulta disponibilidad en tiempo real.',
            ]);

        $stockLabel = $hasVariants
            ? 'Selecciona '.Str::lower($variantAttributeName)
            : (is_null($stock) ? 'A confirmar' : $formatQty($stock).' '.$unitLabelLower);

        $documents = $product->documents;
        $manual = $documents->firstWhere('type', DocumentType::Manual->value);
        $invima = $documents->firstWhere('type', DocumentType::Invima->value);
        $quickGuide = $documents->firstWhere('type', DocumentType::QuickGuide->value);
        $calibrationDocument = $documents->firstWhere('type', DocumentType::CalibrationDocument->value);
        $productVideo = $product->videos->first();
        $primaryDocumentTypes = [
            DocumentType::TechSheet->value,
            DocumentType::Manual->value,
            DocumentType::Invima->value,
            DocumentType::QuickGuide->value,
            DocumentType::CalibrationDocument->value,
        ];
        $secondaryDocuments = $documents->filter(
            fn ($doc) => ! in_array($doc->type, $primaryDocumentTypes, true)
                && (! $techSheet || $doc->id !== $techSheet->id)
                && (! $manual || $doc->id !== $manual->id)
                && (! $invima || $doc->id !== $invima->id)
                && (! $quickGuide || $doc->id !== $quickGuide->id)
                && (! $calibrationDocument || $doc->id !== $calibrationDocument->id)
        );

        $documentTypeLabels = [
            'tech_sheet' => 'Ficha técnica',
            'catalog' => 'Catálogo',
            'certificate' => 'Certificado',
            'manual' => 'Manual de usuario',
            'invima' => 'Registro INVIMA',
            'quick_guide' => 'Guía rápida del producto',
            'calibration_document' => 'Documento de calibracion',
        ];

        $specRows = [
            ['label' => 'SKU',       'value' => $product->sku],
            ['label' => 'Marca',     'value' => $brand],
            ['label' => 'Categoría', 'value' => $categoryName],
        ];
        $sections = [
            ['id' => 'descripcion',     'label' => 'Descripción'],
            ['id' => 'especificaciones', 'label' => 'Especificaciones'],
            ['id' => 'documentos',       'label' => 'Documentos'],
            ['id' => 'alternativas',     'label' => 'Alternativas'],
        ];

        return compact(
            'formatQty', 'mainPhoto', 'galleryPhotos', 'categoryName', 'brand',
            'unitLabel', 'unitLabelLower', 'leadTimeLabel', 'etaLabel',
            'minMultiple', 'stepValue',
            'discountPercent', 'promoLabel', 'activeVariants', 'hasVariants',
            'variantAttributeName', 'minVariantPrice', 'maxVariantPrice',
            'price', 'isRangePrice', 'formattedPrice', 'vatLabel', 'stock', 'canBuy',
            'isLowStock', 'availability', 'stockLabel', 'documents',
            'stockSyncedAt', 'stockIsStale', 'stockFreshnessLabel',
            'manual', 'invima', 'quickGuide', 'calibrationDocument', 'productVideo', 'secondaryDocuments', 'documentTypeLabels',
            'specRows', 'sections',
        );
    }

    /**
     * @return array{
     *   brand:string,
     *   unit:string,
     *   minMultiple:int,
     *   stock:float|null,
     *   stockSyncedAt:CarbonInterface|null,
     *   stockIsStale:bool,
     *   stockFreshnessLabel:string,
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

        $stockSyncedAtRaw = $product->getAttribute('stock_synced_at');
        $stockSyncedAt = $stockSyncedAtRaw !== null
            ? CarbonImmutable::parse((string) $stockSyncedAtRaw)
            : null;
        $staleAfter = max(1, (int) config('contapyme.stock_stale_after', 900));
        $stockIsStale = $stockSyncedAt === null
            || $stockSyncedAt->lt(now()->subSeconds($staleAfter));

        return [
            'brand' => (string) (data_get($product, 'brand') ?: 'Marca no especificada'),
            'unit' => (string) (data_get($product, 'unit') ?: data_get($product, 'unit_label') ?: 'unidad'),
            'minMultiple' => $minMultiple,
            'stock' => $stock,
            'stockSyncedAt' => $stockSyncedAt,
            'stockIsStale' => $stockIsStale,
            'stockFreshnessLabel' => $stockSyncedAt
                ? 'Actualizado '.$stockSyncedAt->diffForHumans()
                : 'Aún no sincronizado',
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

    /**
     * @param  array{list:?string, related_page:int, alternatives_page:int}  $queryState
     */
    private function relatedProducts(Product $product, array $queryState): LengthAwarePaginator
    {
        if (! $product->category_id) {
            return $this->emptyPaginator($product, 'related_page', $queryState);
        }

        $paginator = Product::query()
            ->where('is_active', true)
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->with(['category', 'primaryPhoto', 'photos', 'variantAttribute', 'variants.attributeValue'])
            ->latest('id')
            ->paginate(20, ['*'], 'related_page', (int) ($queryState['related_page'] ?? 1));

        $paginator->appends($this->basePaginationQuery($queryState));

        return $paginator;
    }

    /**
     * @param  array{list:?string, related_page:int, alternatives_page:int}  $queryState
     */
    private function alternativeProducts(Product $product, array $queryState): LengthAwarePaginator
    {
        $query = Product::query()
            ->where('is_active', true)
            ->whereKeyNot($product->id)
            ->with(['category', 'primaryPhoto', 'photos', 'variantAttribute', 'variants.attributeValue'])
            ->latest('id');

        if ($product->category_id) {
            $query->where(function ($builder) use ($product) {
                $builder
                    ->whereNull('category_id')
                    ->orWhere('category_id', '!=', $product->category_id);
            });
        }

        $paginator = $query
            ->paginate(20, ['*'], 'alternatives_page', (int) ($queryState['alternatives_page'] ?? 1));

        $paginator->appends($this->basePaginationQuery($queryState));

        return $paginator;
    }

    /**
     * @param  array{list:?string, related_page:int, alternatives_page:int}  $queryState
     */
    private function buildProductListPayload(
        Product $product,
        LengthAwarePaginator $products,
        string $listKey,
        array $queryState,
    ): array {
        return [
            'html' => view('catalog._products-partial', compact('products', 'listKey'))->render(),
            'controlsHtml' => view('catalog._product-list-controls', compact('products'))->render(),
            'hasMore' => $products->hasMorePages(),
            'currentPage' => $products->currentPage(),
            'nextPage' => $products->currentPage() + 1,
            'pushUrl' => $this->pushUrl($product, $products, $queryState),
        ];
    }

    /**
     * @param  array{list:?string, related_page:int, alternatives_page:int}  $queryState
     */
    private function pushUrl(Product $product, LengthAwarePaginator $products, array $queryState): string
    {
        $query = $this->basePaginationQuery($queryState);

        if ($products->currentPage() <= 1) {
            unset($query[$products->getPageName()]);
        } else {
            $query[$products->getPageName()] = $products->currentPage();
        }

        $queryString = http_build_query($query);

        return $queryString === ''
            ? route('products.show', $product)
            : route('products.show', $product).'?'.$queryString;
    }

    /**
     * @param  array{list:?string, related_page:int, alternatives_page:int}  $queryState
     */
    private function emptyPaginator(Product $product, string $pageName, array $queryState): LengthAwarePaginator
    {
        return new \Illuminate\Pagination\LengthAwarePaginator(
            [],
            0,
            20,
            max(1, (int) ($queryState[$pageName] ?? 1)),
            [
                'path' => route('products.show', $product),
                'pageName' => $pageName,
                'query' => $this->basePaginationQuery($queryState),
            ],
        );
    }

    /**
     * @return array{list:?string, related_page:int, alternatives_page:int}
     */
    private function resolveQueryState(Request $request): array
    {
        $validated = $request->validate([
            'list' => ['nullable', 'string', Rule::in(['related', 'alternatives'])],
            'related_page' => ['nullable', 'integer', 'min:1'],
            'alternatives_page' => ['nullable', 'integer', 'min:1'],
        ]);

        return [
            'list' => $validated['list'] ?? null,
            'related_page' => max(1, (int) ($validated['related_page'] ?? 1)),
            'alternatives_page' => max(1, (int) ($validated['alternatives_page'] ?? 1)),
        ];
    }

    /**
     * @param  array{list:?string, related_page:int, alternatives_page:int}  $queryState
     * @return array<string, int>
     */
    private function basePaginationQuery(array $queryState): array
    {
        $query = [];

        if ((int) ($queryState['related_page'] ?? 1) > 1) {
            $query['related_page'] = (int) $queryState['related_page'];
        }

        if ((int) ($queryState['alternatives_page'] ?? 1) > 1) {
            $query['alternatives_page'] = (int) $queryState['alternatives_page'];
        }

        return $query;
    }
}
