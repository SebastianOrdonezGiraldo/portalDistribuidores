<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Actions\AddVideoAction;
use App\Modules\Catalog\Actions\AttachManualAction;
use App\Modules\Catalog\Actions\AttachTechSheetAction;
use App\Modules\Catalog\Actions\CreateProductAction;
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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductAdminController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);

        $indexOptions = $this->indexFilterOptions();
        $indexContext = $this->resolveIndexContext($request->query(), true, $indexOptions);
        $indexContextQuery = $this->resolveIndexQuery($request->query(), true, $indexOptions);
        $filters = $this->extractFiltersFromContext($indexContext);

        $filteredQuery = $this->buildFilteredQuery($filters);

        $products = (clone $filteredQuery)
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
            ])
            ->when($filters['sort'] === 'newest', fn ($query) => $query->latest())
            ->when($filters['sort'] === 'oldest', fn ($query) => $query->oldest())
            ->when($filters['sort'] === 'name_asc', fn ($query) => $query->orderBy('name'))
            ->when($filters['sort'] === 'name_desc', fn ($query) => $query->orderByDesc('name'))
            ->when($filters['sort'] === 'price_desc', fn ($query) => $query->orderByDesc('price')->orderBy('name'))
            ->when($filters['sort'] === 'price_asc', fn ($query) => $query->orderBy('price')->orderBy('name'))
            ->when($filters['sort'] === 'stock_desc', fn ($query) => $query->orderByDesc('stock')->orderBy('name'))
            ->when($filters['sort'] === 'stock_asc', fn ($query) => $query->orderBy('stock')->orderBy('name'))
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

    public function create(Request $request): View
    {
        $this->authorize('create', Product::class);

        return view('admin.products.form', array_merge(
            [
                'product' => new Product(),
                'indexContextQuery' => $this->resolveIndexQuery($request->query(), true),
            ],
            $this->formViewData(),
        ));
    }

    public function store(
        StoreProductRequest $request,
        CreateProductAction $createAction,
        ProductVariantSyncService $variantSyncService,
        UploadProductPhotoAction $uploadPhotoAction,
        AttachTechSheetAction $attachTechSheetAction,
        AttachManualAction $attachManualAction,
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

        $this->attachMedia($request, $product, $uploadPhotoAction, $attachTechSheetAction, $attachManualAction, $addVideoAction);

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

    public function update(
        UpdateProductRequest $request,
        Product $product,
        UpdateProductAction $updateAction,
        ProductVariantSyncService $variantSyncService,
        UploadProductPhotoAction $uploadPhotoAction,
        AttachTechSheetAction $attachTechSheetAction,
        AttachManualAction $attachManualAction,
        AddVideoAction $addVideoAction,
    ): RedirectResponse {
        $this->authorize('update', $product);

        $productPayload = $this->extractProductPayload($request);
        $validatedPayload = $request->validated();

        $product = DB::transaction(function () use ($updateAction, $variantSyncService, $product, $productPayload, $validatedPayload) {
            $updatedProduct = $updateAction->execute($product, $productPayload);
            $variantSyncService->sync($updatedProduct, $validatedPayload);

            return $updatedProduct->refresh();
        });

        $this->attachMedia($request, $product, $uploadPhotoAction, $attachTechSheetAction, $attachManualAction, $addVideoAction);

        return $this->redirectAfterSave($request, $product, false);
    }

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
                ->with('error', 'Este producto no tiene variantes activas para actualizar.');
        }

        return redirect()
            ->route('admin.products.index', $indexContextQuery)
            ->with('status', 'Stock por variantes actualizado.');
    }

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
     * @return array{categories: \Illuminate\Database\Eloquent\Collection, variantAttributes: \Illuminate\Database\Eloquent\Collection}
     */
    private function formViewData(): array
    {
        return [
            'categories'        => Category::active()->orderBy('name')->get(),
            'variantAttributes' => ProductAttribute::query()->with('values')->orderBy('name')->get(),
        ];
    }

    private function extractProductPayload(StoreProductRequest|UpdateProductRequest $request): array
    {
        $payload = $request->safe()->except([
            'photo',
            'tech_sheet',
            'manual',
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

    /**
     * @param array<int, array<string, mixed>> $rows
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
     * @param array{status: list<string>, media: list<string>, stock: list<string>, sort: list<string>, per_page: list<int>} $options
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
     * @param array<string, mixed> $input
     * @param array{status: list<string>, media: list<string>, stock: list<string>, sort: list<string>, per_page: list<int>}|null $options
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
     * @param array<string, mixed> $input
     * @param array{status: list<string>, media: list<string>, stock: list<string>, sort: list<string>, per_page: list<int>}|null $options
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
     * @param array{q: ?string, category_id: ?int, status: ?string, media: ?string, stock: ?string, sort: string, per_page: int, page: int} $context
     * @return array{q: ?string, category_id: ?int, status: ?string, media: ?string, stock: ?string, sort: string, per_page: int}
     */
    private function extractFiltersFromContext(array $context): array
    {
        unset($context['page']);

        return $context;
    }

    /**
     * @param array{q: ?string, category_id: ?int, status: ?string, media: ?string, stock: ?string, sort: string, per_page: int} $filters
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
     * @param array{q: ?string, category_id: ?int, status: ?string, media: ?string, stock: ?string, sort: string, per_page: int, page: int} $context
     * @param array<string, int|string> $query
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
