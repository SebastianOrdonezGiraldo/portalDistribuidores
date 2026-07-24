<?php

namespace App\Services\Turnstile;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TurnstileVerifier
{
    public function isEnabled(): bool
    {
        return (bool) config('services.turnstile.enabled');
    }

    public function verify(?string $token, ?string $remoteIp = null): bool
    {
        if (! $this->isEnabled()) {
            return true;
        }

        $token = is_string($token) ? trim($token) : '';

        if ($token === '') {
            return false;
        }

        $secret = (string) config('services.turnstile.secret_key', '');

        if ($secret === '') {
            Log::warning('turnstile.verify.missing_secret');

            return false;
        }

        try {
            $payload = [
                'secret' => $secret,
                'response' => $token,
            ];

            if (filled($remoteIp)) {
                $payload['remoteip'] = $remoteIp;
            }

            $response = Http::asForm()
                ->timeout(5)
                ->acceptJson()
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', $payload);

            if (! $response->successful()) {
                Log::warning('turnstile.verify.http_error', [
                    'status' => $response->status(),
                ]);

                return false;
            }

            $success = (bool) $response->json('success', false);

            if (! $success) {
                Log::info('turnstile.verify.rejected', [
                    'error_codes' => $response->json('error-codes', []),
                ]);
            }

            return $success;
        } catch (Throwable $exception) {
            Log::warning('turnstile.verify.exception', [
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
