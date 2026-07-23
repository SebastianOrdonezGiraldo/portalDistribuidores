<?php

namespace App\Rules;

use App\Services\Turnstile\TurnstileVerifier;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TurnstileToken implements ValidationRule
{
    /**
     * Run even when cf-turnstile-response is missing from the request.
     */
    public bool $implicit = true;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $verifier = app(TurnstileVerifier::class);

        if (! $verifier->isEnabled()) {
            return;
        }

        $token = is_string($value) ? $value : null;
        $ip = request()->ip();

        if (! $verifier->verify($token, $ip)) {
            $fail('No pudimos verificar que no eres un robot. Inténtalo de nuevo.');
        }
    }
}
