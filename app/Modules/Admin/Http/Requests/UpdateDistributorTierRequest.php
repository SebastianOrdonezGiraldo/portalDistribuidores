<?php

namespace App\Modules\Admin\Http\Requests;

use App\Modules\Shared\Enums\DistributorTier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDistributorTierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'tier' => ['required', Rule::enum(DistributorTier::class)],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function bodyParameters(): array
    {
        return [
            'tier' => [
                'description' => 'Nivel comercial del distribuidor (plata u oro).',
                'example' => DistributorTier::Gold->value,
            ],
        ];
    }
}
