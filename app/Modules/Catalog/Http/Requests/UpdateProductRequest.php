<?php

namespace App\Modules\Catalog\Http\Requests;

use App\Modules\Catalog\Http\Requests\Concerns\InteractsWithProductUploads;
use App\Modules\Catalog\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateProductRequest extends FormRequest
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
        /** @var Product|null $product */
        $product = $this->route('product');

        return array_merge([
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
            'is_vat_excluded' => ['nullable', 'boolean'],
            'video_url' => ['nullable', 'url', 'max:255'],
        ], $this->productUploadRules());
    }

    public function bodyParameters(): array
    {
        return array_merge([
            'name' => [
                'description' => 'Nombre visible del producto.',
                'example' => 'Guante nitrilo azul',
            ],
            'brand' => [
                'description' => 'Marca comercial del producto.',
                'example' => 'DemoMed',
            ],
            'sku' => [
                'description' => 'SKU unico del producto.',
                'example' => 'GUA-NIT-AZ',
            ],
            'description' => [
                'description' => 'Descripcion comercial o tecnica del producto.',
                'example' => 'Guante de nitrilo para uso medico.',
            ],
            'category_id' => [
                'description' => 'ID de la categoria asociada.',
                'example' => 3,
            ],
            'has_variants' => [
                'description' => 'Indica si el producto se cotiza mediante variantes.',
                'example' => true,
            ],
            'variant_attribute_id' => [
                'description' => 'ID del atributo usado para las variantes existentes.',
                'example' => 2,
            ],
            'new_variant_attribute_name' => [
                'description' => 'Nombre de un nuevo atributo de variante cuando no se usa uno existente.',
                'example' => 'Talla',
            ],
            'variants' => [
                'description' => 'Lista completa de variantes del producto.',
                'example' => [['value' => 'M', 'price' => 12000, 'stock' => 50]],
            ],
            'variants.*.value' => [
                'description' => 'Valor visible de la variante.',
                'example' => 'M',
            ],
            'variants.*.price' => [
                'description' => 'Precio de la variante.',
                'example' => 12000,
            ],
            'variants.*.stock' => [
                'description' => 'Stock disponible de la variante.',
                'example' => 50,
            ],
            'price' => [
                'description' => 'Precio del producto cuando no maneja variantes.',
                'example' => 12000,
            ],
            'stock' => [
                'description' => 'Stock del producto cuando no maneja variantes.',
                'example' => 50,
            ],
            'is_active' => [
                'description' => 'Indica si el producto queda visible en catalogo.',
                'example' => true,
            ],
            'is_vat_excluded' => [
                'description' => 'Indica si el producto esta excluido de IVA.',
                'example' => false,
            ],
            'video_url' => [
                'description' => 'URL opcional de video del producto.',
                'example' => 'https://example.com/video',
            ],
        ], $this->productUploadBodyParameters());
    }

    public function messages(): array
    {
        return $this->productUploadMessages();
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
