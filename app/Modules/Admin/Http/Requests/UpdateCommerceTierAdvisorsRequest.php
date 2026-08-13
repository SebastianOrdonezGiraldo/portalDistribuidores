<?php

namespace App\Modules\Admin\Http\Requests;

use App\Modules\Orders\Support\AdvisorWhatsappNormalizer;
use App\Modules\Shared\Enums\DistributorTier;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCommerceTierAdvisorsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $advisors = $this->input('advisors', []);

        foreach (DistributorTier::cases() as $tier) {
            $data = is_array($advisors[$tier->value] ?? null) ? $advisors[$tier->value] : [];
            $rawWhatsapp = trim((string) ($data['advisor_whatsapp'] ?? ''));

            $advisors[$tier->value] = [
                'advisor_name' => trim((string) ($data['advisor_name'] ?? '')),
                'advisor_email' => strtolower(trim((string) ($data['advisor_email'] ?? ''))),
                'advisor_whatsapp' => AdvisorWhatsappNormalizer::normalize($rawWhatsapp) ?? $rawWhatsapp,
            ];
        }

        $this->merge(['advisors' => $advisors]);
    }

    public function rules(): array
    {
        $rules = [];

        foreach (DistributorTier::cases() as $tier) {
            $prefix = 'advisors.'.$tier->value.'.';
            $rules[$prefix.'advisor_name'] = ['required', 'string', 'max:120'];
            $rules[$prefix.'advisor_email'] = ['required', 'email', 'max:120'];
            $rules[$prefix.'advisor_whatsapp'] = ['required', 'string', 'regex:/^[1-9]\d{7,14}$/'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'advisors.*.advisor_name.required' => 'El nombre del asesor es obligatorio.',
            'advisors.*.advisor_email.required' => 'El correo del asesor es obligatorio.',
            'advisors.*.advisor_email.email' => 'El correo del asesor no es válido.',
            'advisors.*.advisor_whatsapp.required' => 'El WhatsApp del asesor es obligatorio.',
            'advisors.*.advisor_whatsapp.regex' => 'El WhatsApp debe ser un número internacional válido.',
        ];
    }
}
