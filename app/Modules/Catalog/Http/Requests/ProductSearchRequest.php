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
}
