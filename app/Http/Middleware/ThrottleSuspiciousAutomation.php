<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ThrottleSuspiciousAutomation
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isEnabled() || ! $this->isSuspiciousAutomation($request)) {
            return $next($request);
        }

        $maxAttempts = max(1, (int) config('abuse_protection.automation.max_per_minute', 12));
        $key = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return $this->throttledResponse($request, max(1, RateLimiter::availableIn($key)));
        }

        RateLimiter::hit($key, 60);

        return $next($request);
    }

    private function isEnabled(): bool
    {
        return (bool) config('abuse_protection.automation.enabled', true);
    }

    private function isSuspiciousAutomation(Request $request): bool
    {
        $userAgent = Str::lower(trim((string) $request->userAgent()));

        if ($userAgent === '') {
            return true;
        }

        /** @var list<string> $signatures */
        $signatures = config('abuse_protection.automation.signatures', []);

        foreach ($signatures as $signature) {
            if ($signature !== '' && str_contains($userAgent, $signature)) {
                return true;
            }
        }

        return false;
    }

    private function throttleKey(Request $request): string
    {
        $routeName = (string) optional($request->route())->getName();
        $routeName = $routeName !== '' ? $routeName : Str::slug($request->path());
        $ip = $request->ip() ?: 'unknown';

        return "abuse:suspicious:{$routeName}:{$ip}";
    }

    private function throttledResponse(Request $request, int $retryAfterSeconds): Response
    {
        $message = 'Demasiadas solicitudes automatizadas detectadas. Intenta nuevamente en unos segundos.';
        $headers = [
            'Retry-After' => (string) $retryAfterSeconds,
            'X-RateLimit-Reason' => 'suspicious-automation',
        ];

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => $message,
                'retry_after' => $retryAfterSeconds,
            ], 429, $headers);
        }

        return response($message, 429, $headers);
    }
}
