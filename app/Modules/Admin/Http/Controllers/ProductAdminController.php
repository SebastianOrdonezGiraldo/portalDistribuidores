<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Actions\AddVideoAction;
use App\Modules\Catalog\Actions\AttachTechSheetAction;
use App\Modules\Catalog\Actions\CreateProductAction;
use App\Modules\Catalog\Actions\UpdateProductAction;
use App\Modules\Catalog\Actions\UploadProductPhotoAction;
use App\Modules\Catalog\Http\Requests\ImportProductsRequest;
use App\Modules\Catalog\Http\Requests\StoreProductRequest;
use App\Modules\Catalog\Http\Requests\UpdateProductRequest;
use App\Modules\Catalog\Models\ProductAttribute;
use App\Modules\Catalog\Models\ProductDocument;
use App\Modules\Catalog\Models\ProductPhoto;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVideo;
use App\Modules\Catalog\Services\ProductBulkImportService;
use App\Modules\Catalog\Services\ProductVariantSyncService;
use App\Modules\Categories\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductAdminController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);

        $statusOptions = ['active', 'inactive'];
        $mediaOptions = ['with_photo', 'without_photo', 'with_sheet', 'with_video'];
        $stockOptions = ['in_stock', 'no_stock', 'unknown'];
        $sortOptions = ['newest', 'oldest', 'name_asc', 'name_desc', 'price_desc', 'price_asc', 'stock_desc', 'stock_asc'];
        $perPageOptions = [15, 30, 60];

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'status' => ['nullable', 'string', Rule::in($statusOptions)],
            'media' => ['nullable', 'string', Rule::in($mediaOptions)],
            'stock' => ['nullable', 'string', Rule::in($stockOptions)],
            'sort' => ['nullable', 'string', Rule::in($sortOptions)],
            'per_page' => ['nullable', 'integer', Rule::in($perPageOptions)],
        ]);

        $filters = array_merge([
            'q' => null,
            'category_id' => null,
            'status' => null,
            'media' => null,
            'stock' => null,
            'sort' => 'newest',
            'per_page' => 15,
        ], $filters);

        $filteredQuery = Product::query()
            ->when(! empty($filters['q']), function ($query) use ($filters) {
                $term = trim((string) $filters['q']);

                $query->where(function ($subQuery) use ($term) {
                    $subQuery
                        ->where('name', 'like', "%{$term}%")
                        ->orWhere('brand', 'like', "%{$term}%")
                        ->orWhere('sku', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%");
                });
            })
            ->when(! empty($filters['category_id']), fn ($query) => $query->where('category_id', $filters['category_id']))
            ->when(! empty($filters['status']), fn ($query) => $query->where('is_active', $filters['status'] === 'active'))
            ->when($filters['media'] === 'with_photo', fn ($query) => $query->whereHas('photos'))
            ->when($filters['media'] === 'without_photo', fn ($query) => $query->whereDoesntHave('photos'))
            ->when($filters['media'] === 'with_sheet', fn ($query) => $query->whereHas('documents', fn ($documents) => $documents->where('type', 'tech_sheet')))
            ->when($filters['media'] === 'with_video', fn ($query) => $query->whereHas('videos'))
            ->when($filters['stock'] === 'in_stock', fn ($query) => $query->whereNotNull('stock')->where('stock', '>', 0))
            ->when($filters['stock'] === 'no_stock', fn ($query) => $query->whereNotNull('stock')->where('stock', '<=', 0))
            ->when($filters['stock'] === 'unknown', fn ($query) => $query->whereNull('stock'));

        $products = (clone $filteredQuery)
            ->with('category', 'primaryPhoto')
            ->withCount(['photos', 'videos', 'documents'])
            ->when($filters['sort'] === 'newest', fn ($query) => $query->latest())
            ->when($filters['sort'] === 'oldest', fn ($query) => $query->oldest())
            ->when($filters['sort'] === 'name_asc', fn ($query) => $query->orderBy('name'))
            ->when($filters['sort'] === 'name_desc', fn ($query) => $query->orderByDesc('name'))
            ->when($filters['sort'] === 'price_desc', fn ($query) => $query->orderByDesc('price')->orderBy('name'))
            ->when($filters['sort'] === 'price_asc', fn ($query) => $query->orderBy('price')->orderBy('name'))
            ->when($filters['sort'] === 'stock_desc', fn ($query) => $query->orderByDesc('stock')->orderBy('name'))
            ->when($filters['sort'] === 'stock_asc', fn ($query) => $query->orderBy('stock')->orderBy('name'))
            ->paginate((int) $filters['per_page'])
            ->withQueryString();

        $metrics = [
            'total_products' => (clone $filteredQuery)->count(),
            'active_products' => (clone $filteredQuery)->where('is_active', true)->count(),
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
            'statusOptions' => $statusOptions,
            'mediaOptions' => $mediaOptions,
            'stockOptions' => $stockOptions,
            'sortOptions' => $sortOptions,
            'perPageOptions' => $perPageOptions,
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'metrics' => $metrics,
            'activeFiltersCount' => $activeFiltersCount,
        ]);
    }

    public function downloadImportTemplate(): StreamedResponse
    {
        $this->authorize('create', Product::class);

        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'wb');

            if (! is_resource($output)) {
                return;
            }

            // BOM UTF-8 to improve Excel compatibility in Windows.
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['action', 'sku', 'name', 'brand', 'description', 'category_id', 'price', 'stock', 'is_active'], ';');
            fclose($output);
        }, 'plantilla_import_productos.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function import(
        ImportProductsRequest $request,
        ProductBulkImportService $bulkImportService,
    ): RedirectResponse {
        $this->authorize('create', Product::class);

        $report = $bulkImportService->importFromCsv(
            $request->file('file')->getRealPath(),
            (string) $request->input('default_action', 'upsert'),
        );

        $message = sprintf(
            'Importación finalizada. Filas: %d, creados: %d, actualizados: %d, omitidos: %d, errores: %d.',
            (int) $report['total_rows'],
            (int) $report['created'],
            (int) $report['updated'],
            (int) $report['skipped'],
            count($report['errors']),
        );

        return redirect()
            ->route('admin.products.index')
            ->with('status', $message)
            ->with('importReport', $report);
    }

    public function create(): View
    {
        $this->authorize('create', Product::class);

        return view('admin.products.form', [
            'product' => new Product(),
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
            'variantAttributes' => ProductAttribute::query()->with('values')->orderBy('name')->get(),
        ]);
    }

    public function store(
        StoreProductRequest $request,
        CreateProductAction $createAction,
        ProductVariantSyncService $variantSyncService,
        UploadProductPhotoAction $uploadPhotoAction,
        AttachTechSheetAction $attachTechSheetAction,
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

        $this->attachMedia($request, $product, $uploadPhotoAction, $attachTechSheetAction, $addVideoAction);

        return $this->redirectAfterSave($request, $product, true);
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        return view('admin.products.form', [
            'product' => $product->load('photos', 'videos', 'documents', 'category', 'variantAttribute', 'variants.attributeValue'),
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
            'variantAttributes' => ProductAttribute::query()->with('values')->orderBy('name')->get(),
        ]);
    }

    public function update(
        UpdateProductRequest $request,
        Product $product,
        UpdateProductAction $updateAction,
        ProductVariantSyncService $variantSyncService,
        UploadProductPhotoAction $uploadPhotoAction,
        AttachTechSheetAction $attachTechSheetAction,
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

        $this->attachMedia($request, $product, $uploadPhotoAction, $attachTechSheetAction, $addVideoAction);

        return $this->redirectAfterSave($request, $product, false);
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);
        $product->delete();

        return redirect()->route('admin.products.index')->with('status', 'Producto eliminado.');
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

    public function destroyPhoto(Product $product, ProductPhoto $photo): RedirectResponse
    {
        $this->authorize('update', $product);

        if ($photo->product_id !== $product->id) {
            abort(404);
        }

        $path = $photo->path;
        $wasPrimary = $photo->is_primary;
        $photo->delete();

        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        if ($wasPrimary) {
            $nextPrimary = $product->photos()->orderBy('sort_order')->orderBy('id')->first();
            if ($nextPrimary) {
                $nextPrimary->update(['is_primary' => true]);
            }
        }

        return back()->with('status', 'Foto eliminada.');
    }

    public function destroyDocument(Product $product, ProductDocument $document): RedirectResponse
    {
        $this->authorize('update', $product);

        if ($document->product_id !== $product->id) {
            abort(404);
        }

        $path = $document->path;
        $document->delete();

        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        return back()->with('status', 'Documento eliminado.');
    }

    public function destroyVideo(Product $product, ProductVideo $video): RedirectResponse
    {
        $this->authorize('update', $product);

        if ($video->product_id !== $product->id) {
            abort(404);
        }

        $video->delete();

        return back()->with('status', 'Video eliminado.');
    }

    private function attachMedia(
        StoreProductRequest|UpdateProductRequest $request,
        Product $product,
        UploadProductPhotoAction $uploadPhotoAction,
        AttachTechSheetAction $attachTechSheetAction,
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
    }

    private function redirectAfterSave(Request $request, Product $product, bool $created): RedirectResponse
    {
        $status = $created ? 'Producto creado.' : 'Producto actualizado.';
        $afterSave = (string) $request->input('after_save', 'save');

        if ($afterSave === 'index') {
            return redirect()->route('admin.products.index')->with('status', $status);
        }

        if ($afterSave === 'preview') {
            return redirect()->route('products.show', $product)->with('status', $status);
        }

        if ($afterSave === 'new') {
            return redirect()->route('admin.products.create')->with('status', $status.' Puedes crear otro.');
        }

        return redirect()->route('admin.products.edit', $product)->with('status', $status);
    }

    private function extractProductPayload(StoreProductRequest|UpdateProductRequest $request): array
    {
        $payload = $request->safe()->except([
            'photo',
            'tech_sheet',
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
}
