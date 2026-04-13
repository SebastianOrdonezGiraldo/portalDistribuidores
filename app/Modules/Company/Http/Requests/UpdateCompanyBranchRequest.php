<?php

namespace App\Modules\Company\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:180'],
            'city' => ['nullable', 'string', 'max:120'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }
}
