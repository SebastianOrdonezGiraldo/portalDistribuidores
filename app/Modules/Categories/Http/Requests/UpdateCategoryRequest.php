<?php

namespace App\Modules\Categories\Http\Requests;

use App\Modules\Categories\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Category|null $category */
        $category = $this->route('category');

        return [
            'parent_id' => ['nullable', 'integer', 'exists:categories,id', Rule::notIn([$category?->id])],
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:140', Rule::unique('categories', 'slug')->ignore($category?->id)],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'synonyms' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'parent_id' => [
                'description' => 'ID de la categoria padre. No puede ser la misma categoria.',
                'example' => 1,
            ],
            'name' => [
                'description' => 'Nombre visible de la categoria.',
                'example' => 'Insumos medicos',
            ],
            'slug' => [
                'description' => 'Slug unico de la categoria.',
                'example' => 'insumos-medicos',
            ],
            'is_active' => [
                'description' => 'Indica si la categoria queda visible en catalogo.',
                'example' => true,
            ],
            'sort_order' => [
                'description' => 'Orden manual de aparicion.',
                'example' => 10,
            ],
            'synonyms' => [
                'description' => 'Sinonimos separados por coma para mejorar busqueda.',
                'example' => 'material medico, suministros',
            ],
        ];
    }
}
