<?php

namespace App\Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'sku' => ['required', 'string', 'max:80', 'unique:products,sku'],
            'description' => ['nullable', 'string', 'max:4000'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'stock' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'is_active' => ['nullable', 'boolean'],
            'photo' => ['nullable', 'image', 'max:3072'],
            'video_url' => ['nullable', 'url', 'max:255'],
            'tech_sheet' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ];
    }
}
