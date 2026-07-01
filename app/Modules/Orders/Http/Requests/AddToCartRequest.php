<?php

namespace App\Modules\Orders\Http\Requests;

use App\Modules\Catalog\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AddToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'variant_id' => [
                'nullable',
                'integer',
                Rule::exists('product_variants', 'id')->where(fn ($query) => $query
                    ->where('product_id', $this->integer('product_id'))
                    ->where('is_active', true)),
            ],
            'qty' => ['required', 'integer', 'min:1', 'max:10000'],
            'unit_label' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'product_id' => [
                'description' => 'ID del producto que se agregara al carrito.',
                'example' => 12,
            ],
            'variant_id' => [
                'description' => 'ID de la variante seleccionada cuando el producto maneja variantes activas.',
                'example' => 25,
            ],
            'qty' => [
                'description' => 'Cantidad solicitada del producto o variante.',
                'example' => 10,
            ],
            'unit_label' => [
                'description' => 'Unidad visible para la linea del carrito.',
                'example' => 'caja',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $productId = $this->integer('product_id');

            if ($productId <= 0) {
                return;
            }

            $product = Product::query()
                ->withCount(['variants as active_variants_count' => fn ($query) => $query->where('is_active', true)])
                ->find($productId);

            if (! $product) {
                return;
            }

            $variantId = $this->input('variant_id');

            if ((int) $product->active_variants_count > 0 && blank($variantId)) {
                $validator->errors()->add('variant_id', 'Debes seleccionar una variante para este producto.');
            }
        });
    }
}
