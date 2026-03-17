<?php

namespace App\Modules\Catalog\Http\Requests;

use App\Modules\Catalog\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateProductRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'has_variants' => $this->boolean('has_variants'),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Product|null $product */
        $product = $this->route('product');

        return [
            'name' => ['required', 'string', 'max:160'],
            'brand' => ['nullable', 'string', 'max:120'],
            'sku' => ['required', 'string', 'max:80', Rule::unique('products', 'sku')->ignore($product?->id)],
            'description' => ['nullable', 'string', 'max:4000'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'has_variants' => ['nullable', 'boolean'],
            'variant_attribute_id' => [
                'nullable',
                'integer',
                'exists:product_attributes,id',
                Rule::requiredIf(fn () => $this->boolean('has_variants') && ! $this->filled('new_variant_attribute_name')),
            ],
            'new_variant_attribute_name' => [
                'nullable',
                'string',
                'max:120',
                Rule::requiredIf(fn () => $this->boolean('has_variants') && ! $this->filled('variant_attribute_id')),
            ],
            'variants' => [
                Rule::requiredIf(fn () => $this->boolean('has_variants')),
                'array',
                'min:1',
            ],
            'variants.*.value' => [
                Rule::requiredIf(fn () => $this->boolean('has_variants')),
                'string',
                'max:120',
            ],
            'variants.*.price' => [
                Rule::requiredIf(fn () => $this->boolean('has_variants')),
                'numeric',
                'min:0',
                'max:9999999',
            ],
            'variants.*.stock' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'price' => [
                Rule::requiredIf(fn () => ! $this->boolean('has_variants')),
                'nullable',
                'numeric',
                'min:0',
                'max:9999999',
            ],
            'stock' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'is_active' => ['nullable', 'boolean'],
            'photos' => ['nullable', 'array', 'max:10'],
            'photos.*' => ['image', 'max:3072'],
            'video_url' => ['nullable', 'url', 'max:255'],
            'tech_sheet' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->boolean('has_variants')) {
                return;
            }

            $rows = collect((array) $this->input('variants', []))
                ->map(fn (mixed $row) => Str::slug(Str::lower(trim((string) data_get($row, 'value')))))
                ->filter();

            if ($rows->isEmpty()) {
                $validator->errors()->add('variants', 'Debes agregar al menos una variante con valor válido.');

                return;
            }

            if ($rows->count() !== $rows->unique()->count()) {
                $validator->errors()->add('variants', 'No se pueden repetir valores de variante para un mismo producto.');
            }
        });
    }
}
