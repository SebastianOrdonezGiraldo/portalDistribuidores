<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ThrottleAiGenerationRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isAiGenerationRequest($request)) {
            return $next($request);
        }

        [$minuteMax, $hourMax] = $this->resolveLimits($request);
        [$minuteKey, $hourKey] = $this->resolveKeys($request);

        if (
            RateLimiter::tooManyAttempts($minuteKey, $minuteMax)
            || RateLimiter::tooManyAttempts($hourKey, $hourMax)
        ) {
            $retryAfter = max(
                1,
                RateLimiter::availableIn($minuteKey),
                RateLimiter::availableIn($hourKey),
            );

            return $this->throttledResponse($request, $retryAfter);
        }

        RateLimiter::hit($minuteKey, 60);
        RateLimiter::hit($hourKey, 3600);

        return $next($request);
    }

    private function isAiGenerationRequest(Request $request): bool
    {
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH'], true)) {
            return false;
        }

        $routeName = (string) optional($request->route())->getName();
        if ($routeName !== '' && Str::startsWith($routeName, 'ai.')) {
            return true;
        }

        if ($request->is('ai/*') || $request->is('api/ai/*')) {
            return true;
        }

        if ($request->hasAny(['prompt', 'messages', 'input']) && $request->filled('model')) {
            return true;
        }

        return false;
    }

    /**
     * @return array{0:int,1:int}
     */
    private function resolveLimits(Request $request): array
    {
        if ($request->user()) {
            return [
                max(1, (int) config('abuse_protection.ai_generation.auth_per_minute', 30)),
                max(1, (int) config('abuse_protection.ai_generation.auth_per_hour', 500)),
            ];
        }

        return [
            max(1, (int) config('abuse_protection.ai_generation.guest_per_minute', 3)),
            max(1, (int) config('abuse_protection.ai_generation.guest_per_hour', 30)),
        ];
    }

    /**
     * @return array{0:string,1:string}
     */
    private function resolveKeys(Request $request): array
    {
        $identifier = $this->actorKey($request);

        return [
            "abuse:ai:minute:{$identifier}",
            "abuse:ai:hour:{$identifier}",
        ];
    }

    private function actorKey(Request $request): string
    {
        if ($request->user()) {
            return 'user:'.$request->user()->getAuthIdentifier();
        }

        $ip = $request->ip() ?: 'unknown';
        $ua = Str::lower((string) $request->userAgent());
        $uaHash = substr(sha1($ua), 0, 12);

        return "guest:{$ip}:{$uaHash}";
    }

    private function throttledResponse(Request $request, int $retryAfterSeconds): Response
    {
        $message = 'Limite de solicitudes de generacion alcanzado. Intenta nuevamente en unos segundos.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => $message,
                'retry_after' => $retryAfterSeconds,
            ], 429, ['Retry-After' => (string) $retryAfterSeconds]);
        }

        return response($message, 429, ['Retry-After' => (string) $retryAfterSeconds]);
    }
}
