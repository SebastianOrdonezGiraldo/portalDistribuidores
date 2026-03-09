<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Actions\AddVideoAction;
use App\Modules\Catalog\Actions\AttachTechSheetAction;
use App\Modules\Catalog\Actions\CreateProductAction;
use App\Modules\Catalog\Actions\UpdateProductAction;
use App\Modules\Catalog\Actions\UploadProductPhotoAction;
use App\Modules\Catalog\Http\Requests\StoreProductRequest;
use App\Modules\Catalog\Http\Requests\UpdateProductRequest;
use App\Modules\Catalog\Models\Product;
use App\Modules\Categories\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductAdminController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $products = Product::query()
            ->with('category', 'primaryPhoto')
            ->when(! empty($filters['q']), function ($query) use ($filters) {
                $term = trim((string) $filters['q']);

                $query->where(function ($subQuery) use ($term) {
                    $subQuery
                        ->where('name', 'like', "%{$term}%")
                        ->orWhere('sku', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%");
                });
            })
            ->when(! empty($filters['category_id']), fn ($query) => $query->where('category_id', $filters['category_id']))
            ->when(! empty($filters['status']), fn ($query) => $query->where('is_active', $filters['status'] === 'active'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'filters' => $filters,
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Product::class);

        return view('admin.products.form', [
            'product' => new Product(),
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(
        StoreProductRequest $request,
        CreateProductAction $createAction,
        UploadProductPhotoAction $uploadPhotoAction,
        AttachTechSheetAction $attachTechSheetAction,
        AddVideoAction $addVideoAction,
    ): RedirectResponse {
        $this->authorize('create', Product::class);

        $product = $createAction->execute($request->safe()->except(['photo', 'tech_sheet', 'video_url']));
        $this->attachMedia($request, $product, $uploadPhotoAction, $attachTechSheetAction, $addVideoAction);

        return redirect()->route('admin.products.index')->with('status', 'Producto creado.');
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        return view('admin.products.form', [
            'product' => $product->load('photos', 'videos', 'documents', 'category'),
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(
        UpdateProductRequest $request,
        Product $product,
        UpdateProductAction $updateAction,
        UploadProductPhotoAction $uploadPhotoAction,
        AttachTechSheetAction $attachTechSheetAction,
        AddVideoAction $addVideoAction,
    ): RedirectResponse {
        $this->authorize('update', $product);

        $updateAction->execute($product, $request->safe()->except(['photo', 'tech_sheet', 'video_url']));
        $this->attachMedia($request, $product, $uploadPhotoAction, $attachTechSheetAction, $addVideoAction);

        return redirect()->route('admin.products.index')->with('status', 'Producto actualizado.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);
        $product->delete();

        return redirect()->route('admin.products.index')->with('status', 'Producto eliminado.');
    }

    private function attachMedia(
        StoreProductRequest|UpdateProductRequest $request,
        Product $product,
        UploadProductPhotoAction $uploadPhotoAction,
        AttachTechSheetAction $attachTechSheetAction,
        AddVideoAction $addVideoAction,
    ): void {
        if ($request->hasFile('photo')) {
            $uploadPhotoAction->execute($product, $request->file('photo'));
        }

        if ($request->filled('video_url')) {
            $addVideoAction->execute($product, $request->string('video_url')->toString(), $product->videos()->count() + 1);
        }

        if ($request->hasFile('tech_sheet')) {
            $attachTechSheetAction->execute($product, $request->file('tech_sheet'));
        }
    }
}
