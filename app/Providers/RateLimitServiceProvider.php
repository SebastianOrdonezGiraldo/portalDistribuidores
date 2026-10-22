<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

/**
 * Named rate limiters used by web routes and middleware (config/abuse_protection.php).
 */
class RateLimitServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('login-attempts', function (Request $request): array {
            $ip = $request->ip() ?: 'unknown';
            $perMinute = max(1, (int) config('abuse_protection.login.per_minute', 20));
            $perHour = max(1, (int) config('abuse_protection.login.per_hour', 250));

            return [
                Limit::perMinute($perMinute)->by("login:minute:{$ip}"),
                Limit::perHour($perHour)->by("login:hour:{$ip}"),
            ];
        });

        RateLimiter::for('account-creation', function (Request $request): array {
            $ip = $request->ip() ?: 'unknown';
            $email = Str::lower(trim((string) $request->input('email', '')));
            $hourLimit = max(1, (int) config('abuse_protection.registration.per_hour', 5));
            $dayLimit = max(1, (int) config('abuse_protection.registration.per_day', 25));
            $emailKey = $email !== '' ? $email : 'unknown';

            return [
                Limit::perHour($hourLimit)->by("register:hour:ip:{$ip}"),
                Limit::perDay($dayLimit)->by("register:day:ip:{$ip}"),
                Limit::perHour(max(1, min($hourLimit, 3)))->by("register:hour:email:{$emailKey}:{$ip}"),
            ];
        });

        RateLimiter::for('api-endpoints', fn (Request $request): array => $this->perActorLimits($request, 'api', 'api', [
            'auth_per_minute' => 180,
            'guest_per_minute' => 60,
            'auth_per_hour' => 3600,
            'guest_per_hour' => 600,
        ]));

        RateLimiter::for('catalog-scraping', fn (Request $request): array => $this->perActorLimits($request, 'catalog', 'catalog', [
            'auth_per_minute' => 120,
            'guest_per_minute' => 40,
            'auth_per_hour' => 2400,
            'guest_per_hour' => 450,
        ]));

        RateLimiter::for('ai-generation', fn (Request $request): array => $this->perActorLimits($request, 'ai_generation', 'ai', [
            'auth_per_minute' => 30,
            'guest_per_minute' => 3,
            'auth_per_hour' => 500,
            'guest_per_hour' => 30,
        ]));
    }

    /**
     * Per-minute and per-hour limits that are higher for authenticated users.
     *
     * @param  array{auth_per_minute:int, guest_per_minute:int, auth_per_hour:int, guest_per_hour:int}  $defaults
     * @return list<Limit>
     */
    private function perActorLimits(Request $request, string $configKey, string $keyPrefix, array $defaults): array
    {
        $audience = $request->user() !== null ? 'auth' : 'guest';
        $perMinute = max(1, (int) config("abuse_protection.{$configKey}.{$audience}_per_minute", $defaults["{$audience}_per_minute"]));
        $perHour = max(1, (int) config("abuse_protection.{$configKey}.{$audience}_per_hour", $defaults["{$audience}_per_hour"]));
        $actor = $this->rateLimitActor($request);

        return [
            Limit::perMinute($perMinute)->by("{$keyPrefix}:minute:{$actor}"),
            Limit::perHour($perHour)->by("{$keyPrefix}:hour:{$actor}"),
        ];
    }

    /**
     * Build a stable rate-limit key without relying only on raw IP.
     */
    private function rateLimitActor(Request $request): string
    {
        if ($request->user()) {
            return 'user:'.$request->user()->getAuthIdentifier();
        }

        $ip = $request->ip() ?: 'unknown';
        $ua = Str::lower((string) $request->userAgent());
        $uaHash = substr(sha1($ua), 0, 12);

        return "guest:{$ip}:{$uaHash}";
    }
}
