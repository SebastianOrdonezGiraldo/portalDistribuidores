<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Categories\Actions\CreateCategoryAction;
use App\Modules\Categories\Actions\UpdateCategoryAction;
use App\Modules\Categories\Http\Requests\StoreCategoryRequest;
use App\Modules\Categories\Http\Requests\UpdateCategoryRequest;
use App\Modules\Categories\Models\Category;
use App\Modules\Categories\Queries\CategoryDescendantsQuery;
use App\Modules\Categories\Queries\CategoryTreeQuery;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryAdminController extends Controller
{
    public function __construct(
        private readonly CategoryDescendantsQuery $categoryDescendantsQuery,
    ) {}

    public function index(Request $request, CategoryTreeQuery $treeQuery): View
    {
        $this->authorize('viewAny', Category::class);

        $statusOptions = ['active', 'inactive'];
        $withProductsOptions = ['yes', 'no'];
        $sortOptions = ['tree', 'name_asc', 'name_desc', 'updated_desc', 'updated_asc'];

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', Rule::in($statusOptions)],
            'with_products' => ['nullable', 'string', Rule::in($withProductsOptions)],
            'sort' => ['nullable', 'string', Rule::in($sortOptions)],
        ]);

        $filters = array_merge([
            'q' => null,
            'status' => null,
            'with_products' => null,
            'sort' => 'tree',
        ], $filters);

        $categories = $treeQuery->execute(activeOnly: false, filters: $filters);
        $flattenedCategories = $this->flattenTree($categories);
        $allCategoriesQuery = Category::query();

        $metrics = [
            'total_categories' => (clone $allCategoriesQuery)->count(),
            'active_categories' => (clone $allCategoriesQuery)->active()->count(),
            'with_products' => (clone $allCategoriesQuery)->has('products')->count(),
            'synonyms' => (int) DB::table('category_synonyms')->count(),
            'filtered_total' => $flattenedCategories->count(),
        ];

        $activeFiltersCount = collect([
            $filters['q'],
            $filters['status'],
            $filters['with_products'],
            $filters['sort'] !== 'tree' ? $filters['sort'] : null,
        ])->filter(fn ($value) => filled($value))->count();

        return view('admin.categories.index', [
            'categories' => $categories,
            'metrics' => $metrics,
            'filters' => $filters,
            'statusOptions' => $statusOptions,
            'withProductsOptions' => $withProductsOptions,
            'sortOptions' => $sortOptions,
            'activeFiltersCount' => $activeFiltersCount,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Category::class);

        return view('admin.categories.form', [
            'category' => new Category(),
            'allCategories' => Category::query()->orderBy('name')->get(),
            'synonyms' => '',
        ]);
    }

    public function store(StoreCategoryRequest $request, CreateCategoryAction $action): RedirectResponse
    {
        $this->authorize('create', Category::class);
        $category = $action->execute($request->payload());
        $this->syncSynonyms($category, $request->input('synonyms'));

        return $this->redirectAfterSave($request, $category, true);
    }

    public function edit(Category $category): View
    {
        $this->authorize('update', $category);

        return view('admin.categories.form', [
            'category' => $category->load('synonyms'),
            'allCategories' => Category::query()->where('id', '!=', $category->id)->orderBy('name')->get(),
            'synonyms' => $category->synonyms->pluck('term')->implode(', '),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category, UpdateCategoryAction $action): RedirectResponse
    {
        $this->authorize('update', $category);

        $payload = collect($request->validated())->except('synonyms')->all();
        $parentId = $payload['parent_id'] ?? null;

        if ($parentId && $this->isDescendantOf(categoryId: $category->id, candidateParentId: (int) $parentId)) {
            return back()
                ->withErrors(['parent_id' => 'No se puede asignar una subcategoría como padre.'])
                ->withInput();
        }

        $action->execute($category, $payload);
        $this->syncSynonyms($category, $request->input('synonyms'));

        return $this->redirectAfterSave($request, $category, false);
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        $productsCount = $category->products()->count();

        if ($productsCount > 0) {
            $label = $productsCount === 1 ? 'producto asociado' : 'productos asociados';

            return back()->with('error', "No se puede eliminar la categoría porque tiene {$productsCount} {$label}. Reasigna esos productos a otra categoría primero.");
        }

        $childrenCount = $category->children()->count();

        if ($childrenCount > 0) {
            $label = $childrenCount === 1 ? 'subcategoría' : 'subcategorías';

            return back()->with('error', "No se puede eliminar la categoría porque tiene {$childrenCount} {$label}. Reorganiza el árbol primero.");
        }

        try {
            $category->delete();
        } catch (QueryException $exception) {
            $sqlState = (string) ($exception->errorInfo[0] ?? '');

            if (in_array($sqlState, ['23001', '23503'], true)) {
                return back()->with('error', 'No se pudo eliminar la categoría porque está relacionada con otros registros.');
            }

            throw $exception;
        }

        return redirect()->route('admin.categories.index')->with('status', 'Categoría eliminada.');
    }

    public function setStatus(Request $request, Category $category): RedirectResponse
    {
        $this->authorize('update', $category);

        $payload = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $isActive = (bool) $payload['is_active'];
        $category->update(['is_active' => $isActive]);

        return back()->with('status', $isActive ? 'Categoría activada.' : 'Categoría desactivada.');
    }

    private function syncSynonyms(Category $category, ?string $csvTerms): void
    {
        $terms = collect(explode(',', (string) $csvTerms))
            ->map(fn (string $term) => Str::of($term)->trim()->lower()->toString())
            ->filter()
            ->unique()
            ->values();

        $category->synonyms()->delete();

        foreach ($terms as $term) {
            $category->synonyms()->create(['term' => $term]);
        }
    }

    private function isDescendantOf(int $categoryId, int $candidateParentId): bool
    {
        // Una sola query: carga toda la jerarquía y busca en memoria.
        // Reemplaza el bucle original que hacía 1 query por nivel de profundidad.
        $descendants = $this->categoryDescendantsQuery->execute($categoryId);

        return in_array($candidateParentId, $descendants, true);
    }

    private function redirectAfterSave(Request $request, Category $category, bool $created): RedirectResponse
    {
        $status = $created ? 'Categoría creada.' : 'Categoría actualizada.';
        $afterSave = (string) $request->input('after_save', 'index');

        if ($afterSave === 'stay') {
            return redirect()->route('admin.categories.edit', $category)->with('status', $status);
        }

        if ($afterSave === 'new') {
            return redirect()->route('admin.categories.create')->with('status', $status.' Puedes crear otra.');
        }

        return redirect()->route('admin.categories.index')->with('status', $status);
    }

    private function flattenTree($categories)
    {
        return collect($categories)->flatMap(function (Category $category) {
            $children = $this->flattenTree($category->children ?? collect());

            return collect([$category])->concat($children);
        });
    }
}
