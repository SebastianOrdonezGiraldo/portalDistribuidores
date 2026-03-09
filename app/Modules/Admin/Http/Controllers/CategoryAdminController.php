<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Categories\Actions\CreateCategoryAction;
use App\Modules\Categories\Actions\UpdateCategoryAction;
use App\Modules\Categories\Http\Requests\StoreCategoryRequest;
use App\Modules\Categories\Http\Requests\UpdateCategoryRequest;
use App\Modules\Categories\Models\Category;
use App\Modules\Categories\Queries\CategoryTreeQuery;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryAdminController extends Controller
{
    public function index(CategoryTreeQuery $treeQuery): View
    {
        $this->authorize('viewAny', Category::class);

        return view('admin.categories.index', [
            'categories' => $treeQuery->execute(activeOnly: false),
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

        return redirect()->route('admin.categories.index')->with('status', 'Categoría creada.');
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
        $action->execute($category, collect($request->validated())->except('synonyms')->all());
        $this->syncSynonyms($category, $request->input('synonyms'));

        return redirect()->route('admin.categories.index')->with('status', 'Categoría actualizada.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        $productsCount = $category->products()->count();

        if ($productsCount > 0) {
            $label = $productsCount === 1 ? 'producto asociado' : 'productos asociados';

            return back()->with('error', "No se puede eliminar la categoría porque tiene {$productsCount} {$label}. Reasigna esos productos a otra categoría primero.");
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
}
