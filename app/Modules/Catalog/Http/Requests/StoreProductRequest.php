<?php

namespace App\Modules\Catalog\Http\Requests;

use App\Modules\Catalog\Http\Requests\Concerns\InteractsWithProductUploads;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreProductRequest extends FormRequest
{
    use InteractsWithProductUploads;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'has_variants' => $this->boolean('has_variants'),
            'is_vat_excluded' => $this->boolean('is_vat_excluded'),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge([
            'name' => ['required', 'string', 'max:160'],
            'brand' => ['nullable', 'string', 'max:120'],
            'sku' => ['required', 'string', 'max:80', 'unique:products,sku'],
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
            'variants.*.stock' => [
                Rule::requiredIf(fn () => $this->boolean('has_variants') && $this->boolean('is_active')),
                'nullable',
                'numeric',
                'min:0',
                'max:9999999',
            ],
            'price' => [
                Rule::requiredIf(fn () => ! $this->boolean('has_variants')),
                'nullable',
                'numeric',
                'min:0',
                'max:9999999',
            ],
            'stock' => [
                Rule::requiredIf(fn () => ! $this->boolean('has_variants') && $this->boolean('is_active')),
                'nullable',
                'numeric',
                'min:0',
                'max:9999999',
            ],
            'is_active' => ['nullable', 'boolean'],
            'is_vat_excluded' => ['nullable', 'boolean'],
            'video_url' => ['nullable', 'url', 'max:255'],
        ], $this->productUploadRules());
    }

    public function messages(): array
    {
        return array_merge($this->productUploadMessages(), [
            'stock.required' => 'El stock es obligatorio para publicar el producto.',
            'variants.*.stock.required' => 'El stock de cada variante es obligatorio para publicar el producto.',
        ]);
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
                $validator->errors()->add('variants', 'Debes agregar al menos una variante con valor valido.');

                return;
            }

            if ($rows->count() !== $rows->unique()->count()) {
                $validator->errors()->add('variants', 'No se pueden repetir valores de variante para un mismo producto.');
            }
        });
    }
}
