<?php

namespace App\Providers;

use App\Services\Security\RuntimeSecurityGuard;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

/**
 * Password defaults, runtime configuration guards and canonical URL/scheme.
 */
class SecurityServiceProvider extends ServiceProvider
{
    public function boot(RuntimeSecurityGuard $guard): void
    {
        $this->configurePasswordDefaults();

        if (! app()->environment(['local', 'testing'])) {
            $violations = $guard->violations();

            $guard->handle($violations);

            if (! in_array(RuntimeSecurityGuard::NON_CANONICAL_APP_URL, $violations, true)) {
                URL::forceRootUrl(rtrim((string) config('app.url'), '/'));
            }
        }

        $configuredScheme = strtolower((string) parse_url((string) config('app.url'), PHP_URL_SCHEME));

        if ($configuredScheme === 'https' || app()->isProduction()) {
            URL::forceScheme('https');
        }
    }

    /**
     * Enforce stronger password requirements outside tests.
     */
    private function configurePasswordDefaults(): void
    {
        Password::defaults(function () {
            $rule = Password::min(8);

            if (config('app.env') !== 'testing') {
                $rule->mixedCase()->letters()->numbers()->symbols();
            }

            return $rule;
        });
    }
}
