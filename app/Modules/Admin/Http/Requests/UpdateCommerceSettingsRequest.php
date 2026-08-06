<?php

namespace App\Modules\Admin\Http\Requests;

use App\Modules\Shared\Enums\GoldThresholdBasis;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class UpdateCommerceSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $percent = $this->input('silver_markup_percent');

        if (is_string($percent)) {
            $normalized = str_replace(',', '.', trim($percent));
            $this->merge(['silver_markup_percent' => $normalized]);
        }

        $this->merge([
            'silver_min_order_enabled' => $this->boolean('silver_min_order_enabled'),
            'gold_min_order_enabled' => $this->boolean('gold_min_order_enabled'),
            'gold_pricing_threshold_enabled' => $this->boolean('gold_pricing_threshold_enabled'),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'silver_markup_percent' => ['required', 'numeric', 'min:0', 'max:100', 'decimal:0,2'],
            'silver_rounding_multiple' => ['required', 'integer', 'in:100,500,1000'],
            'silver_min_order_enabled' => ['required', 'boolean'],
            'silver_min_order_amount' => ['required', 'integer', 'min:1'],
            'gold_min_order_enabled' => ['required', 'boolean'],
            'gold_min_order_amount' => ['required', 'integer', 'min:1'],
            'gold_pricing_threshold_enabled' => ['required', 'boolean'],
            'gold_pricing_threshold_amount' => ['required', 'integer', 'min:1'],
            'gold_pricing_threshold_basis' => ['required', 'string', Rule::enum(GoldThresholdBasis::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'silver_markup_percent.required' => 'El incremento del precio Plata es obligatorio.',
            'silver_markup_percent.numeric' => 'El incremento debe ser un número válido.',
            'silver_markup_percent.min' => 'El incremento no puede ser negativo.',
            'silver_markup_percent.max' => 'El incremento no puede superar el 100%.',
            'silver_markup_percent.decimal' => 'El incremento admite como máximo dos decimales.',
            'silver_rounding_multiple.required' => 'El múltiplo de redondeo es obligatorio.',
            'silver_rounding_multiple.integer' => 'El múltiplo de redondeo debe ser un entero.',
            'silver_rounding_multiple.in' => 'El múltiplo de redondeo debe ser $100, $500 o $1.000.',
            'silver_min_order_amount.min' => 'El pedido mínimo Plata debe ser mayor que cero.',
            'gold_min_order_amount.min' => 'El pedido mínimo Oro debe ser mayor que cero.',
            'gold_pricing_threshold_amount.min' => 'El umbral de precios Oro debe ser mayor que cero.',
            'gold_pricing_threshold_basis.Illuminate\Validation\Rules\Enum' => 'La base de evaluación del umbral Oro no es válida.',
        ];
    }

    /**
     * Convert the validated percentage string into integer basis points without float.
     *
     * Examples: "5" -> 500, "5.00" -> 500, "6.50" -> 650, "7.25" -> 725.
     */
    public function silverMarkupBasisPoints(): int
    {
        $raw = (string) $this->validated('silver_markup_percent');
        $raw = trim($raw);

        if ($raw === '' || ! preg_match('/^\d+(\.\d{1,2})?$/', $raw)) {
            throw new InvalidArgumentException('Porcentaje de incremento inválido.');
        }

        [$whole, $fraction] = array_pad(explode('.', $raw, 2), 2, '');
        $whole = $whole === '' ? '0' : $whole;
        $fraction = str_pad(substr($fraction, 0, 2), 2, '0');

        if (! ctype_digit($whole) || ! ctype_digit($fraction)) {
            throw new InvalidArgumentException('Porcentaje de incremento inválido.');
        }

        return ((int) $whole) * 100 + (int) $fraction;
    }

    public function silverRoundingMultiple(): int
    {
        return (int) $this->validated('silver_rounding_multiple');
    }

    public function silverMinOrderEnabled(): bool
    {
        return (bool) $this->validated('silver_min_order_enabled');
    }

    public function silverMinOrderAmount(): int
    {
        return (int) $this->validated('silver_min_order_amount');
    }

    public function goldMinOrderEnabled(): bool
    {
        return (bool) $this->validated('gold_min_order_enabled');
    }

    public function goldMinOrderAmount(): int
    {
        return (int) $this->validated('gold_min_order_amount');
    }

    public function goldPricingThresholdEnabled(): bool
    {
        return (bool) $this->validated('gold_pricing_threshold_enabled');
    }

    public function goldPricingThresholdAmount(): int
    {
        return (int) $this->validated('gold_pricing_threshold_amount');
    }

    public function goldPricingThresholdBasis(): GoldThresholdBasis
    {
        return GoldThresholdBasis::from((string) $this->validated('gold_pricing_threshold_basis'));
    }
}
