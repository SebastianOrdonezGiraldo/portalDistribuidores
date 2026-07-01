<?php

namespace App\Modules\Admin\Http\Requests;

use App\Modules\Shared\Enums\DistributorStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDistributorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'status' => ['required', 'string', Rule::in(array_map(
                static fn (DistributorStatus $status) => $status->value,
                DistributorStatus::cases(),
            ))],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'name' => [
                'description' => 'Nombre de la empresa distribuidora.',
                'example' => 'Distribuciones Demo SAS',
            ],
            'status' => [
                'description' => 'Estado operativo del distribuidor.',
                'example' => DistributorStatus::Active->value,
            ],
        ];
    }
}
