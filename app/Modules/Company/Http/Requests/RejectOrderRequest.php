<?php

namespace App\Modules\Company\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'approval_note' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'approval_note.required' => 'Debes indicar el motivo del rechazo.',
        ];
    }
}
