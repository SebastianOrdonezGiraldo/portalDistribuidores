<?php

namespace App\Modules\Catalog\Http\Requests;

use App\Modules\Shared\ValueObjects\ProductSearchQuery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'term' => ['nullable', 'string', 'max:120'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'include_children' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(ProductSearchQuery::AVAILABLE_SORTS)],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function bodyParameters(): array
    {
        return [];
    }

    public function queryParameters(): array
    {
        return [
            'term' => [
                'description' => 'Texto libre para buscar por nombre, marca, SKU o descripcion.',
                'example' => 'jeringa',
            ],
            'category_id' => [
                'description' => 'ID de categoria usada como filtro.',
                'example' => 3,
            ],
            'include_children' => [
                'description' => 'Incluye productos de categorias hijas cuando el filtro por categoria esta activo.',
                'example' => true,
            ],
            'sort' => [
                'description' => 'Ordenamiento del catalogo.',
                'example' => 'name_asc',
            ],
            'page' => [
                'description' => 'Numero de pagina para paginacion.',
                'example' => 1,
            ],
            'per_page' => [
                'description' => 'Cantidad de productos por pagina.',
                'example' => 24,
            ],
        ];
    }
}
