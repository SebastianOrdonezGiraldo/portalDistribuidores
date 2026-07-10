<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Services\InventoryPdfGenerator;
use App\Modules\Catalog\Actions\AddVideoAction;
use App\Modules\Catalog\Actions\AttachManualAction;
use App\Modules\Catalog\Actions\AttachProtectedProductDocumentAction;
use App\Modules\Catalog\Actions\AttachTechSheetAction;
use App\Modules\Catalog\Actions\CreateProductAction;
use App\Modules\Catalog\Actions\DuplicateProductAction;
use App\Modules\Catalog\Actions\UpdateProductAction;
use App\Modules\Catalog\Actions\UploadProductPhotoAction;
use App\Modules\Catalog\Http\Requests\StoreProductRequest;
use App\Modules\Catalog\Http\Requests\UpdateProductRequest;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductAttribute;
use App\Modules\Catalog\Services\ProductStockService;
use App\Modules\Catalog\Services\ProductVariantSyncService;
use App\Modules\Categories\Models\Category;
use App\Modules\Shared\Enums\DocumentType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class ProductAdminController extends Controller
{
    /**
     * Listar productos.
     *
     * @group Admin
     *
     * @authenticated
     *
     * @queryParam q string Busqueda por SKU o nombre. Example: CAT
     * @queryParam category_id integer ID de categoria. Example: 3
     * @queryParam status string active o inactive. Example: active
     * @queryParam media string Filtro de media. Example: with_photo
     * @queryParam stock string Filtro de stock. Example: in_stock
     * @queryParam sort string Orden. Example: newest
     * @queryParam per_page integer Tamano de pagina. Example: 20
     *
     * @response 200 {"content":"Vista HTML de productos"}
     * @response 403 {"message":"No autorizado"}
     * @response 422 {"message":"Filtros invalidos"}
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);

        $indexOptions = $this->indexFilterOptions();
        $indexContext = $this->resolveIndexContext($request->query(), true, $indexOptions);
        $indexContextQuery = $this->resolveIndexQuery($request->query(), true, $indexOptions);
        $filters = $this->extractFiltersFromContext($indexContext);

        $filteredQuery = $this->buildFilteredQuery($filters);

        $products = $this->applySortToProductQuery(
            (clone $filteredQuery)
                ->with([
                    'category',
                    'primaryPhoto',
                    'photos',
                    'variantAttribute',
                    'variants' => fn ($query) => $query
                        ->where('is_active', true)
                        ->with('attributeValue'),
                ])
                ->withCount([
                    'photos',
                    'videos',
                    'documents',
                    'variants as active_variants_count' => fn ($query) => $query->where('is_active', true),
                ]),
            (string) $filters['sort'],
        )
            ->paginate((int) $filters['per_page'], ['*'], 'page', (int) $indexContext['page'])
            ->withQueryString();

        $metrics = [
            'total_products' => (clone $filteredQuery)->count(),
            'active_products' => (clone $filteredQuery)->active()->count(),
            'inactive_products' => (clone $filteredQuery)->where('is_active', false)->count(),
            'with_photo' => (clone $filteredQuery)->whereHas('photos')->count(),
            'without_stock' => (clone $filteredQuery)->where(function ($query) {
                $query->whereNull('stock')->orWhere('stock', '<=', 0);
            })->count(),
        ];

        $activeFiltersCount = collect([
            $filters['q'],
            $filters['category_id'],
            $filters['status'],
            $filters['media'],
            $filters['stock'],
        ])->filter(fn ($value) => filled($value))->count();

        return view('admin.products.index', [
            'products' => $products,
            'filters' => $filters,
            'indexContextQuery' => $indexContextQuery,
            'statusOptions' => $indexOptions['status'],
            'mediaOptions' => $indexOptions['media'],
            'stockOptions' => $indexOptions['stock'],
            'sortOptions' => $indexOptions['sort'],
            'perPageOptions' => $indexOptions['per_page'],
            'categories' => Category::active()->orderBy('name')->get(['id', 'name']),
            'metrics' => $metrics,
            'activeFiltersCount' => $activeFiltersCount,
        ]);
    }

    /**
     * Descargar inventario en PDF.
     *
     * Usa los mismos filtros del listado de productos.
     *
     * @group Admin
     *
     * @authenticated
     *
     * @response 200 {"content":"Descarga binaria PDF"}
     * @response 403 {"message":"No autorizado"}
     * @response 422 {"message":"Filtros invalidos"}
     */
    public function downloadInventoryPdf(
        Request $request,
        InventoryPdfGenerator $inventoryPdfGenerator,
    ): Response {
        $this->authorize('viewAny', Product::class);

        $indexOptions = $this->indexFilterOptions();
        $indexContext = $this->resolveIndexContext($request->query(), true, $indexOptions);
        $filters = $this->extractFiltersFromContext($indexContext);

        $products = $this->applySortToProductQuery(
            $this->buildFilteredQuery($filters)->with([
                'category',
                'variantAttribute',
                'documents',
                'videos',
                'variants' => fn ($query) => $query
                    ->where('is_active', true)
                    ->with('attributeValue'),
            ]),
            (string) $filters['sort'],
        )->get();

        $pdf = $inventoryPdfGenerator->generate(
            $products,
            $this->resolveInventoryAppliedFilters($filters),
            $request->user()?->name,
        );

        $fileName = 'saldos_inventario_'.now()->setTimezone(config('app.timezone'))->format('Ymd_His').'.pdf';

        return $pdf->download($fileName);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Product::class);

        return view('admin.products.form', array_merge(
            [
                'product' => new Product,
                'indexContextQuery' => $this->resolveIndexQuery($request->query(), true),
            ],
            $this->formViewData(),
        ));
    }

    /**
     * Crear producto.
     *
     * @group Admin
     *
     * @authenticated
     *
     * @bodyParam name string required Nombre. Example: Guante quirurgico
     * @bodyParam brand string Marca. Example: Import
     * @bodyParam sku string required SKU unico. Example: GUA-001
     * @bodyParam description string Descripcion. Example: Caja x 100 unidades
     * @bodyParam category_id integer required ID de categoria. Example: 3
     * @bodyParam price number Precio si no tiene variantes. Example: 15000
     * @bodyParam stock number Stock si no tiene variantes. Example: 100
     * @bodyParam has_variants boolean Indica variantes. Example: false
     * @bodyParam variants array Variantes cuando `has_variants=true`.
     * @bodyParam is_active boolean Publicado. Example: true
     * @bodyParam is_vat_excluded boolean Excluido de IVA. Example: false
     * @bodyParam photos file[] Fotos del producto.
     * @bodyParam tech_sheet file Ficha tecnica.
     * @bodyParam manual file Manual.
     * @bodyParam invima file Documento INVIMA.
     * @bodyParam quick_guide file Guia rapida.
     * @bodyParam calibration_document file Documento de calibracion.
     * @bodyParam video_url string URL de video. Example: https://example.com/video
     *
     * @response 302 {"redirect":"admin.products.edit|admin.products.index|admin.products.create|products.show"}
     * @response 403 {"message":"No autorizado"}
     * @response 413 {"message":"Archivo demasiado grande"}
     * @response 422 {"message":"Datos invalidos"}
     */
    public function store(
        StoreProductRequest $request,
        CreateProductAction $createAction,
        ProductVariantSyncService $variantSyncService,
        UploadProductPhotoAction $uploadPhotoAction,
        AttachTechSheetAction $attachTechSheetAction,
        AttachManualAction $attachManualAction,
        AttachProtectedProductDocumentAction $attachProtectedProductDocumentAction,
        AddVideoAction $addVideoAction,
    ): RedirectResponse {
        $this->authorize('create', Product::class);

        $productPayload = $this->extractProductPayload($request);
        $validatedPayload = $request->validated();

        $product = DB::transaction(function () use ($createAction, $variantSyncService, $productPayload, $validatedPayload) {
            $createdProduct = $createAction->execute($productPayload);
            $variantSyncService->sync($createdProduct, $validatedPayload);

            return $createdProduct->refresh();
        });

        $this->attachMedia($request, $product, $uploadPhotoAction, $attachTechSheetAction, $attachManualAction, $attachProtectedProductDocumentAction, $addVideoAction);

        return $this->redirectAfterSave($request, $product, true);
    }

    public function edit(Request $request, Product $product): View
    {
        $this->authorize('update', $product);

        return view('admin.products.form', array_merge(
            [
                'product' => $product->load('photos', 'videos', 'documents', 'category', 'variantAttribute', 'variants.attributeValue'),
                'indexContextQuery' => $this->resolveIndexQuery($request->query(), true),
            ],
            $this->formViewData(),
        ));
    }

    /**
     * Actualizar producto.
     *
     * @group Admin
     *
     * @authenticated
     *
     * @urlParam product integer required ID de producto. Example: 10
     *
     * @bodyParam name string required Nombre. Example: Guante quirurgico
     * @bodyParam sku string required SKU unico. Example: GUA-001
     * @bodyParam category_id integer required ID de categoria. Example: 3
     * @bodyParam price number Precio si no tiene variantes. Example: 15000
     * @bodyParam stock number Stock si no tiene variantes. Example: 100
     * @bodyParam has_variants boolean Indica variantes. Example: false
     * @bodyParam variants array Variantes cuando `has_variants=true`.
     * @bodyParam is_active boolean Publicado. Example: true
     * @bodyParam photos file[] Fotos nuevas.
     *
     * @response 302 {"redirect":"admin.products.edit|admin.products.index|admin.products.create|products.show"}
     * @response 403 {"message":"No autorizado"}
     * @response 404 {"message":"Producto no encontrado"}
     * @response 413 {"message":"Archivo demasiado grande"}
     * @response 422 {"message":"Datos invalidos"}
     */
    public function update(
        UpdateProductRequest $request,
        Product $product,
        UpdateProductAction $updateAction,
        ProductVariantSyncService $variantSyncService,
        UploadProductPhotoAction $uploadPhotoAction,
        AttachTechSheetAction $attachTechSheetAction,
        AttachManualAction $attachManualAction,
        AttachProtectedProductDocumentAction $attachProtectedProductDocumentAction,
        AddVideoAction $addVideoAction,
    ): RedirectResponse {
        $this->authorize('update', $product);

        $this->assertContaPymeStockWasNotSubmitted($request, $product);

        $productPayload = $this->extractProductPayload($request);
        $validatedPayload = $request->validated();

        $product = DB::transaction(function () use ($updateAction, $variantSyncService, $product, $productPayload, $validatedPayload) {
            $updatedProduct = $updateAction->execute($product, $productPayload);
            $variantSyncService->sync($updatedProduct, $validatedPayload);

            return $updatedProduct->refresh();
        });

        $this->attachMedia($request, $product, $uploadPhotoAction, $attachTechSheetAction, $attachManualAction, $attachProtectedProductDocumentAction, $addVideoAction);

        return $this->redirectAfterSave($request, $product, false);
    }

    /**
     * Eliminar producto.
     *
     * @group Admin
     *
     * @authenticated
     *
     * @urlParam product integer required ID de producto. Example: 10
     *
     * @response 302 {"redirect":"admin.products.index"}
     * @response 403 {"message":"No autorizado"}
     * @response 404 {"message":"Producto no encontrado"}
     */
    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $indexContextInput = (array) $request->input('index_context', []);
        $indexContext = $this->resolveIndexContext($indexContextInput, true);
        $indexContextQuery = $this->resolveIndexQuery($indexContextInput, true);

        $product->delete();

        return redirect()
            ->route('admin.products.index', $this->resolveDeleteIndexQuery($indexContext, $indexContextQuery))
            ->with('status', 'Producto eliminado.');
    }

    public function duplicate(
        Request $request,
        Product $product,
        DuplicateProductAction $duplicateProductAction,
    ): RedirectResponse {
        $this->authorize('view', $product);
        $this->authorize('create', Product::class);

        $indexContextInput = (array) $request->input('index_context', []);
        $indexContextQuery = $this->resolveIndexQuery($indexContextInput, true);

        try {
            $duplicate = $duplicateProductAction->execute($product);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('admin.products.index', $indexContextQuery)
                ->with('error', 'No fue posible duplicar el producto. Revisa que los archivos del producto original existan.');
        }

        return redirect()
            ->route('admin.products.edit', array_merge(['product' => $duplicate], $indexContextQuery))
            ->with('status', 'Producto duplicado como copia inactiva.');
    }

    /**
     * Validar disponibilidad de SKU.
     *
     * @group Admin
     *
     * @authenticated
     *
     * @queryParam sku string required SKU a validar. Example: GUA-001
     * @queryParam ignore integer Producto a ignorar en edicion. Example: 10
     *
     * @response 200 {"available":true,"message":"SKU disponible."}
     * @response 403 {"message":"No autorizado"}
     * @response 422 {"message":"Datos invalidos"}
     * @response 429 {"message":"Has realizado demasiados intentos."}
     */
    public function checkSku(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $payload = $request->validate([
            'sku' => ['required', 'string', 'max:80'],
            'ignore' => ['nullable', 'integer', 'exists:products,id'],
        ]);

        $exists = Product::query()
            ->where('sku', $payload['sku'])
            ->when(! empty($payload['ignore']), fn ($query) => $query->whereKeyNot($payload['ignore']))
            ->exists();

        return response()->json([
            'available' => ! $exists,
            'message' => $exists ? 'Este SKU ya está en uso.' : 'SKU disponible.',
        ]);
    }

    /**
     * Cambiar estado de producto.
     *
     * @group Admin
     *
     * @authenticated
     *
     * @urlParam product integer required ID de producto. Example: 10
     *
     * @bodyParam is_active boolean required Nuevo estado. Example: false
     *
     * @response 302 {"redirect":"back"}
     * @response 403 {"message":"No autorizado"}
     * @response 422 {"message":"Datos invalidos"}
     */
    public function setStatus(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $payload = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $isActive = (bool) $payload['is_active'];
        $product->update(['is_active' => $isActive]);

        return back()->with('status', $isActive ? 'Producto activado.' : 'Producto desactivado.');
    }

    /**
     * Actualizar stock de producto simple.
     *
     * @group Admin
     *
     * @authenticated
     *
     * @urlParam product integer required ID de producto. Example: 10
     *
     * @bodyParam stock number Stock nuevo o nulo. Example: 120
     *
     * @response 302 {"redirect":"admin.products.index"}
     * @response 403 {"message":"No autorizado"}
     * @response 422 {"message":"Datos invalidos o producto con variantes"}
     */
    public function setStock(
        Request $request,
        Product $product,
        ProductStockService $stockService,
    ): RedirectResponse {
        $this->authorize('update', $product);

        $payload = $request->validate([
            'stock' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
        ]);

        $indexContextQuery = $this->resolveIndexQuery((array) $request->input('index_context', []), true);
        $stock = array_key_exists('stock', $payload) && $payload['stock'] !== null
            ? (float) $payload['stock']
            : null;

        $updated = $stockService->updateSimpleProductStock($product, $stock);

        if (! $updated) {
            return redirect()
                ->route('admin.products.index', $indexContextQuery)
                ->with('error', 'Este producto usa variantes activas. Actualiza el stock por variante.');
        }

        return redirect()
            ->route('admin.products.index', $indexContextQuery)
            ->with('status', 'Stock actualizado.');
    }

    /**
     * Actualizar stock de variantes.
     *
     * @group Admin
     *
     * @authenticated
     *
     * @urlParam product integer required ID de producto. Example: 10
     *
     * @bodyParam variants array required Lista de variantes activas con stock.
     * @bodyParam variants[].id integer required ID de variante. Example: 30
     * @bodyParam variants[].stock number Stock nuevo o nulo. Example: 50
     *
     * @response 302 {"redirect":"admin.products.index"}
     * @response 403 {"message":"No autorizado"}
     * @response 422 {"message":"Datos invalidos"}
     */
    public function setVariantStocks(
        Request $request,
        Product $product,
        ProductStockService $stockService,
    ): RedirectResponse {
        $this->authorize('update', $product);

        $payload = $request->validate([
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('product_variants', 'id')->where(
                    fn ($query) => $query
                        ->where('product_id', $product->id)
                        ->where('is_active', true),
                ),
            ],
            'variants.*.stock' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
        ]);

        $indexContextQuery = $this->resolveIndexQuery((array) $request->input('index_context', []), true);
        $rows = collect((array) $payload['variants'])
            ->map(static function (array $row): array {
                $stock = array_key_exists('stock', $row) && $row['stock'] !== null
                    ? (float) $row['stock']
                    : null;

                return [
                    'id' => (int) $row['id'],
                    'stock' => $stock,
                ];
            })
            ->values()
            ->all();

        $updated = $stockService->updateVariantStocks($product, $rows);

        if (! $updated) {
            return redirect()
                ->route('admin.products.index', $indexContextQuery)
                ->with('error', 'Este producto no tiene variantes activas editables para actualizar.');
        }

        return redirect()
            ->route('admin.products.index', $indexContextQuery)
            ->with('status', 'Stock por variantes actualizado.');
    }

    /**
     * Ejecutar accion masiva sobre productos.
     *
     * @group Admin
     *
     * @authenticated
     *
     * @bodyParam action string required activate, deactivate o delete. Example: activate
     * @bodyParam product_ids integer[] required IDs de productos. Example: [10,11]
     *
     * @response 302 {"redirect":"admin.products.index"}
     * @response 403 {"message":"No autorizado"}
     * @response 422 {"message":"Datos invalidos"}
     */
    public function bulkAction(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', Product::class);

        $indexOptions = $this->indexFilterOptions();
        $payload = $request->validate([
            'action' => ['required', 'string', Rule::in(['activate', 'deactivate', 'delete'])],
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['required', 'integer', 'distinct', 'exists:products,id'],
        ]);

        $indexContextInput = (array) $request->input('index_context', []);
        $indexContext = $this->resolveIndexContext($indexContextInput, true, $indexOptions);
        $indexContextQuery = $this->resolveIndexQuery($indexContextInput, true, $indexOptions);

        $action = (string) $payload['action'];
        $productIds = collect($payload['product_ids'])
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        $products = Product::query()->whereKey($productIds)->get();

        if ($products->count() !== $productIds->count()) {
            return redirect()
                ->route('admin.products.index', $indexContextQuery)
                ->with('status', 'Algunos productos seleccionados ya no existen. Intenta nuevamente.');
        }

        $ability = $action === 'delete' ? 'delete' : 'update';
        $products->each(fn (Product $product) => $this->authorize($ability, $product));

        $affectedRows = 0;
        DB::transaction(function () use ($action, $productIds, &$affectedRows): void {
            if ($action === 'activate') {
                $affectedRows = Product::query()->whereKey($productIds)->update(['is_active' => true]);

                return;
            }

            if ($action === 'deactivate') {
                $affectedRows = Product::query()->whereKey($productIds)->update(['is_active' => false]);

                return;
            }

            $affectedRows = Product::query()->whereKey($productIds)->delete();
        });

        $indexQuery = $action === 'delete'
            ? $this->resolveDeleteIndexQuery($indexContext, $indexContextQuery)
            : $indexContextQuery;

        $noun = $affectedRows === 1 ? 'producto' : 'productos';
        $status = match ($action) {
            'activate' => "{$affectedRows} {$noun} activado(s).",
            'deactivate' => "{$affectedRows} {$noun} desactivado(s).",
            default => "{$affectedRows} {$noun} eliminado(s).",
        };

        return redirect()->route('admin.products.index', $indexQuery)->with('status', $status);
    }

    private function attachMedia(
        StoreProductRequest|UpdateProductRequest $request,
        Product $product,
        UploadProductPhotoAction $uploadPhotoAction,
        AttachTechSheetAction $attachTechSheetAction,
        AttachManualAction $attachManualAction,
        AttachProtectedProductDocumentAction $attachProtectedProductDocumentAction,
        AddVideoAction $addVideoAction,
    ): void {
        if ($request->hasFile('photos')) {
            $existingSortOrder = $product->photos()->max('sort_order') ?? -1;

            foreach ($request->file('photos') as $index => $photo) {
                $uploadPhotoAction->execute($product, $photo, $existingSortOrder + $index + 1);
            }
        }

        if ($request->filled('video_url')) {
            $addVideoAction->execute($product, $request->string('video_url')->toString(), $product->videos()->count() + 1);
        }

        if ($request->hasFile('tech_sheet')) {
            $attachTechSheetAction->execute($product, $request->file('tech_sheet'));
        }

        if ($request->hasFile('manual')) {
            $attachManualAction->execute($product, $request->file('manual'));
        }

        if ($request->hasFile('invima')) {
            $attachProtectedProductDocumentAction->execute($product, $request->file('invima'), DocumentType::Invima, 'invima');
        }

        if ($request->hasFile('quick_guide')) {
            $attachProtectedProductDocumentAction->execute($product, $request->file('quick_guide'), DocumentType::QuickGuide, 'quick_guide');
        }

        if ($request->hasFile('calibration_document')) {
            $attachProtectedProductDocumentAction->execute($product, $request->file('calibration_document'), DocumentType::CalibrationDocument, 'calibration_document');
        }
    }

    private function redirectAfterSave(Request $request, Product $product, bool $created): RedirectResponse
    {
        $status = $created ? 'Producto creado.' : 'Producto actualizado.';
        $afterSave = (string) $request->input('after_save', 'save');
        $indexContextQuery = $this->resolveIndexQuery((array) $request->input('index_context', []), true);

        if ($afterSave === 'index') {
            return redirect()->route('admin.products.index', $indexContextQuery)->with('status', $status);
        }

        if ($afterSave === 'preview') {
            return redirect()->route('products.show', $product)->with('status', $status);
        }

        if ($afterSave === 'new') {
            return redirect()->route('admin.products.create', $indexContextQuery)->with('status', $status.' Puedes crear otro.');
        }

        return redirect()->route('admin.products.edit', array_merge(['product' => $product], $indexContextQuery))->with('status', $status);
    }

    /**
     * @return array{categories: Collection, variantAttributes: Collection}
     */
    private function formViewData(): array
    {
        return [
            'categories' => Category::active()->orderBy('name')->get(),
            'variantAttributes' => ProductAttribute::query()->with('values')->orderBy('name')->get(),
        ];
    }

    private function extractProductPayload(StoreProductRequest|UpdateProductRequest $request): array
    {
        $payload = $request->safe()->except([
            'photo',
            'tech_sheet',
            'manual',
            'invima',
            'quick_guide',
            'calibration_document',
            'video_url',
            'has_variants',
            'variant_attribute_id',
            'new_variant_attribute_name',
            'variants',
        ]);

        if ($request->boolean('has_variants')) {
            $payload['price'] = $this->resolveVariantBootstrapPrice((array) $request->input('variants', []));
            $payload['stock'] = null;
        }

        return $payload;
    }

    private function assertContaPymeStockWasNotSubmitted(UpdateProductRequest $request, Product $product): void
    {
        if (! $product->isStockManagedByContaPyme()) {
            return;
        }

        if (! $request->has('stock') && ! $request->boolean('has_variants') && ! $request->has('variants')) {
            return;
        }

        throw ValidationException::withMessages([
            'stock' => 'El stock de este producto es administrado por ContaPyme.',
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function resolveVariantBootstrapPrice(array $rows): float
    {
        $prices = collect($rows)
            ->map(fn (mixed $row) => data_get($row, 'price'))
            ->filter(fn ($value) => is_numeric($value))
            ->map(fn ($value) => round((float) $value, 2));

        if ($prices->isEmpty()) {
            return 0.0;
        }

        return (float) max(0, $prices->min());
    }

    /**
     * @return array{status: list<string>, media: list<string>, stock: list<string>, sort: list<string>, per_page: list<int>}
     */
    private function indexFilterOptions(): array
    {
        return [
            'status' => ['active', 'inactive'],
            'media' => ['with_photo', 'without_photo', 'with_sheet', 'with_video'],
            'stock' => ['in_stock', 'no_stock', 'unknown'],
            'sort' => ['newest', 'oldest', 'name_asc', 'name_desc', 'price_desc', 'price_asc', 'stock_desc', 'stock_asc'],
            'per_page' => [15, 30, 60],
        ];
    }

    /**
     * @param  array{status: list<string>, media: list<string>, stock: list<string>, sort: list<string>, per_page: list<int>}  $options
     * @return array<string, mixed>
     */
    private function indexContextRules(array $options, bool $includePage = false): array
    {
        $rules = [
            'q' => ['nullable', 'string', 'max:120'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'status' => ['nullable', 'string', Rule::in($options['status'])],
            'media' => ['nullable', 'string', Rule::in($options['media'])],
            'stock' => ['nullable', 'string', Rule::in($options['stock'])],
            'sort' => ['nullable', 'string', Rule::in($options['sort'])],
            'per_page' => ['nullable', 'integer', Rule::in($options['per_page'])],
        ];

        if ($includePage) {
            $rules['page'] = ['nullable', 'integer', 'min:1'];
        }

        return $rules;
    }

    /**
     * @return array{q: ?string, category_id: ?int, status: ?string, media: ?string, stock: ?string, sort: string, per_page: int, page: int}
     */
    private function defaultIndexContext(): array
    {
        return [
            'q' => null,
            'category_id' => null,
            'status' => null,
            'media' => null,
            'stock' => null,
            'sort' => 'newest',
            'per_page' => 15,
            'page' => 1,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array{status: list<string>, media: list<string>, stock: list<string>, sort: list<string>, per_page: list<int>}|null  $options
     * @return array{q: ?string, category_id: ?int, status: ?string, media: ?string, stock: ?string, sort: string, per_page: int, page: int}
     */
    private function resolveIndexContext(array $input, bool $includePage = false, ?array $options = null): array
    {
        $options ??= $this->indexFilterOptions();
        $defaults = $this->defaultIndexContext();
        $validated = validator($input, $this->indexContextRules($options, $includePage))->validate();

        if (! $includePage) {
            unset($defaults['page']);
        }

        /** @var array{q: ?string, category_id: ?int, status: ?string, media: ?string, stock: ?string, sort: string, per_page: int, page: int} $context */
        $context = array_merge($defaults, $validated);

        return $context;
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array{status: list<string>, media: list<string>, stock: list<string>, sort: list<string>, per_page: list<int>}|null  $options
     * @return array<string, int|string>
     */
    private function resolveIndexQuery(array $input, bool $includePage = false, ?array $options = null): array
    {
        $options ??= $this->indexFilterOptions();
        $validated = validator($input, $this->indexContextRules($options, $includePage))->validate();

        return collect($validated)
            ->reject(fn (mixed $value) => $value === null || $value === '')
            ->all();
    }

    /**
     * @param  array{q: ?string, category_id: ?int, status: ?string, media: ?string, stock: ?string, sort: string, per_page: int, page: int}  $context
     * @return array{q: ?string, category_id: ?int, status: ?string, media: ?string, stock: ?string, sort: string, per_page: int}
     */
    private function extractFiltersFromContext(array $context): array
    {
        unset($context['page']);

        return $context;
    }

    /**
     * @param  array{q: ?string, category_id: ?int, status: ?string, media: ?string, stock: ?string, sort: string, per_page: int}  $filters
     * @return Builder<Product>
     */
    private function buildFilteredQuery(array $filters): Builder
    {
        return Product::query()
            ->when(! empty($filters['q']), fn ($query) => $query->adminSearch($filters['q']))
            ->when(! empty($filters['category_id']), fn ($query) => $query->where('category_id', $filters['category_id']))
            ->when(! empty($filters['status']), fn ($query) => $query->where('is_active', $filters['status'] === 'active'))
            ->when($filters['media'] === 'with_photo', fn ($query) => $query->whereHas('photos'))
            ->when($filters['media'] === 'without_photo', fn ($query) => $query->whereDoesntHave('photos'))
            ->when($filters['media'] === 'with_sheet', fn ($query) => $query->whereHas('documents', fn ($documents) => $documents->where('type', DocumentType::TechSheet)))
            ->when($filters['media'] === 'with_video', fn ($query) => $query->whereHas('videos'))
            ->when($filters['stock'] === 'in_stock', fn ($query) => $query->whereNotNull('stock')->where('stock', '>', 0))
            ->when($filters['stock'] === 'no_stock', fn ($query) => $query->whereNotNull('stock')->where('stock', '<=', 0))
            ->when($filters['stock'] === 'unknown', fn ($query) => $query->whereNull('stock'));
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    private function applySortToProductQuery(Builder $query, string $sort): Builder
    {
        return match ($sort) {
            'oldest' => $query->oldest(),
            'name_asc' => $query->orderBy('name'),
            'name_desc' => $query->orderByDesc('name'),
            'price_desc' => $query->orderByDesc('price')->orderBy('name'),
            'price_asc' => $query->orderBy('price')->orderBy('name'),
            'stock_desc' => $query->orderByDesc('stock')->orderBy('name'),
            'stock_asc' => $query->orderBy('stock')->orderBy('name'),
            default => $query->latest(),
        };
    }

    /**
     * @param  array{q: ?string, category_id: ?int, status: ?string, media: ?string, stock: ?string, sort: string, per_page: int}  $filters
     * @return list<string>
     */
    private function resolveInventoryAppliedFilters(array $filters): array
    {
        $summary = [];

        if (filled($filters['q'])) {
            $summary[] = 'Busqueda: '.(string) $filters['q'];
        }

        if (! empty($filters['category_id'])) {
            $categoryName = Category::query()
                ->whereKey((int) $filters['category_id'])
                ->value('name');

            $summary[] = 'Categoria: '.($categoryName ?: '#'.$filters['category_id']);
        }

        if (! empty($filters['status'])) {
            $summary[] = 'Disponibilidad: '.match ($filters['status']) {
                'active' => 'Disponible',
                'inactive' => 'Inactivo',
                default => (string) $filters['status'],
            };
        }

        if (! empty($filters['media'])) {
            $summary[] = 'Media: '.match ($filters['media']) {
                'with_photo' => 'Con foto',
                'without_photo' => 'Sin foto',
                'with_sheet' => 'Con ficha tecnica',
                'with_video' => 'Con video',
                default => (string) $filters['media'],
            };
        }

        if (! empty($filters['stock'])) {
            $summary[] = 'Stock: '.match ($filters['stock']) {
                'in_stock' => 'Con stock',
                'no_stock' => 'Sin stock',
                'unknown' => 'Sin definir',
                default => (string) $filters['stock'],
            };
        }

        $summary[] = 'Orden: '.match ((string) $filters['sort']) {
            'newest' => 'Mas recientes',
            'oldest' => 'Mas antiguos',
            'name_asc' => 'Nombre A-Z',
            'name_desc' => 'Nombre Z-A',
            'price_desc' => 'Mayor precio',
            'price_asc' => 'Menor precio',
            'stock_desc' => 'Mayor stock',
            'stock_asc' => 'Menor stock',
            default => (string) $filters['sort'],
        };

        return $summary;
    }

    /**
     * @param  array{q: ?string, category_id: ?int, status: ?string, media: ?string, stock: ?string, sort: string, per_page: int, page: int}  $context
     * @param  array<string, int|string>  $query
     * @return array<string, int|string>
     */
    private function resolveDeleteIndexQuery(array $context, array $query): array
    {
        if ($query === []) {
            return [];
        }

        $filters = $this->extractFiltersFromContext($context);
        $total = (clone $this->buildFilteredQuery($filters))->count();

        if ($total === 0) {
            unset($query['page']);

            return $query;
        }

        $lastPage = (int) ceil($total / max(1, (int) $filters['per_page']));

        if ((int) $context['page'] > $lastPage) {
            if ($lastPage <= 1) {
                unset($query['page']);
            } else {
                $query['page'] = $lastPage;
            }
        }

        return $query;
    }
}
