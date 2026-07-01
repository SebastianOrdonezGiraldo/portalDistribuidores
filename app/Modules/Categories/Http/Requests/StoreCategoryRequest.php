<?php

namespace App\Modules\Categories\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', 'unique:categories,slug'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'synonyms' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'parent_id' => [
                'description' => 'ID de la categoria padre. Se omite para categorias raiz.',
                'example' => 1,
            ],
            'name' => [
                'description' => 'Nombre visible de la categoria.',
                'example' => 'Insumos medicos',
            ],
            'slug' => [
                'description' => 'Slug opcional; si se omite se genera desde el nombre.',
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

    public function payload(): array
    {
        return [
            'parent_id' => $this->integer('parent_id') ?: null,
            'name' => $this->string('name')->toString(),
            'slug' => $this->filled('slug') ? $this->string('slug')->toString() : Str::slug($this->string('name')->toString()),
            'is_active' => $this->boolean('is_active', true),
            'sort_order' => $this->integer('sort_order', 0),
        ];
    }
}
