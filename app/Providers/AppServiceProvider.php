<?php

namespace App\Providers;

use App\Models\User;
use App\Modules\Admin\Policies\DistributorPolicy;
use App\Modules\Admin\Policies\UserPolicy;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductDocument;
use App\Modules\Catalog\Policies\ProductPolicy;
use App\Modules\Catalog\Queries\PostgresSearchEngine;
use App\Modules\Categories\Models\Category;
use App\Modules\Categories\Policies\CategoryPolicy;
use App\Modules\Company\Policies\CompanyPolicy;
use App\Modules\Documents\Policies\ProductDocumentPolicy;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Policies\OrderPolicy;
use App\Modules\Orders\Services\Cart\CartService;
use App\Modules\Shared\Contracts\SearchEngineInterface;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SearchEngineInterface::class, PostgresSearchEngine::class);

        if (! app()->environment(['local', 'testing'])) {
            $this->app->register(\Scoutapm\Laravel\Providers\ScoutApmServiceProvider::class);
        }
    }

    public function boot(): void
    {
        $this->configureAbuseProtectionRateLimiters();
        $this->configurePasswordDefaults();

        if (! app()->environment(['local', 'testing'])) {
            $violations = $this->runtimeSecurityViolations();

            $this->handleRuntimeSecurityViolations($violations);

            if (! in_array('APP_URL debe apuntar a un host canonico fuera de local/testing.', $violations, true)) {
                URL::forceRootUrl(rtrim((string) config('app.url'), '/'));
            }
        }

        $configuredScheme = strtolower((string) parse_url((string) config('app.url'), PHP_URL_SCHEME));

        if ($configuredScheme === 'https' || app()->isProduction()) {
            URL::forceScheme('https');
        }

        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(ProductDocument::class, ProductDocumentPolicy::class);
        Gate::policy(Distributor::class, DistributorPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        // Gates para el panel de empresa — admin global siempre puede, luego delega a CompanyPolicy
        Gate::define('editCompany', function (User $user) {
            if ($user->isAdmin()) {
                return true;
            }

            return (new CompanyPolicy)->editCompany($user);
        });
        Gate::define('manageBranches', function (User $user) {
            if ($user->isAdmin()) {
                return true;
            }

            return $user->canManageBranches();
        });
        Gate::define('approveOrders', function (User $user) {
            if ($user->isAdmin()) {
                return true;
            }

            return $user->canApproveOrders();
        });

        View::composer('layouts.app', function ($view): void {
            $count = app(CartService::class)->sessionCount();

            // In testing environment, skip caching to avoid stale data between tests
            if (app()->environment('testing')) {
                $footerTopCategories = Category::active()
                    ->withCount('products')
                    ->orderByDesc('products_count')
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->limit(4)
                    ->get(['id', 'name']);
                $headerQuickCategories = Category::active()
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->limit(8)
                    ->get(['id', 'name']);
            } else {
                $footerTopCategories = Cache::remember('footer_top_categories', 3600, fn () => Category::active()
                    ->withCount('products')
                    ->orderByDesc('products_count')
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->limit(4)
                    ->get(['id', 'name'])
                );
                $headerQuickCategories = Cache::remember('header_quick_categories', 3600, fn () => Category::active()
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->limit(8)
                    ->get(['id', 'name'])
                );
            }
            $view->with([
                'navCartCount' => $count,
                'cartCount' => $count,
                'footerTopCategories' => $footerTopCategories,
                'headerQuickCategories' => $headerQuickCategories,
                'pendingApprovalCount' => 0,
            ]);
        });
    }

    private function configureAbuseProtectionRateLimiters(): void
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

        RateLimiter::for('api-endpoints', function (Request $request): array {
            $isAuthenticated = $request->user() !== null;
            $perMinute = $isAuthenticated
                ? max(1, (int) config('abuse_protection.api.auth_per_minute', 180))
                : max(1, (int) config('abuse_protection.api.guest_per_minute', 60));
            $perHour = $isAuthenticated
                ? max(1, (int) config('abuse_protection.api.auth_per_hour', 3600))
                : max(1, (int) config('abuse_protection.api.guest_per_hour', 600));
            $actor = $this->rateLimitActor($request);

            return [
                Limit::perMinute($perMinute)->by("api:minute:{$actor}"),
                Limit::perHour($perHour)->by("api:hour:{$actor}"),
            ];
        });

        RateLimiter::for('catalog-scraping', function (Request $request): array {
            $isAuthenticated = $request->user() !== null;
            $perMinute = $isAuthenticated
                ? max(1, (int) config('abuse_protection.catalog.auth_per_minute', 120))
                : max(1, (int) config('abuse_protection.catalog.guest_per_minute', 40));
            $perHour = $isAuthenticated
                ? max(1, (int) config('abuse_protection.catalog.auth_per_hour', 2400))
                : max(1, (int) config('abuse_protection.catalog.guest_per_hour', 450));
            $actor = $this->rateLimitActor($request);

            return [
                Limit::perMinute($perMinute)->by("catalog:minute:{$actor}"),
                Limit::perHour($perHour)->by("catalog:hour:{$actor}"),
            ];
        });

        RateLimiter::for('ai-generation', function (Request $request): array {
            $isAuthenticated = $request->user() !== null;
            $perMinute = $isAuthenticated
                ? max(1, (int) config('abuse_protection.ai_generation.auth_per_minute', 30))
                : max(1, (int) config('abuse_protection.ai_generation.guest_per_minute', 3));
            $perHour = $isAuthenticated
                ? max(1, (int) config('abuse_protection.ai_generation.auth_per_hour', 500))
                : max(1, (int) config('abuse_protection.ai_generation.guest_per_hour', 30));
            $actor = $this->rateLimitActor($request);

            return [
                Limit::perMinute($perMinute)->by("ai:minute:{$actor}"),
                Limit::perHour($perHour)->by("ai:hour:{$actor}"),
            ];
        });
    }

    private function configurePasswordDefaults(): void
    {
        Password::defaults(function () {
            $rule = Password::min(8);

            if ((bool) config('app.env') !== 'testing') {
                $rule->mixedCase()->letters()->numbers()->symbols();
            }

            return $rule;
        });
    }

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

    /**
     * @return list<string>
     */
    private function runtimeSecurityViolations(): array
    {
        $violations = [];

        if ((bool) config('app.debug')) {
            $violations[] = 'APP_DEBUG debe estar deshabilitado fuera de local/testing.';
        }

        if (config('session.secure') !== true) {
            $violations[] = 'SESSION_SECURE_COOKIE debe ser true fuera de local/testing.';
        }

        if (! $this->hasCanonicalAppUrl((string) config('app.url'))) {
            $violations[] = 'APP_URL debe apuntar a un host canonico fuera de local/testing.';
        }

        if (! $this->usesSecureDatabaseTransport()) {
            $violations[] = 'DB_SSLMODE debe ser require, verify-ca o verify-full fuera de local/testing.';
        }

        if ((string) config('filesystems.order_pdfs_disk', 'private') === 'public') {
            $violations[] = 'Los PDFs de pedidos no pueden usar el disco public fuera de local/testing.';
        }

        if ((string) config('filesystems.tech_sheets_disk', 'private') === 'public') {
            $violations[] = 'Las fichas tecnicas no pueden usar el disco public fuera de local/testing.';
        }

        return $violations;
    }

    /**
     * @param  list<string>  $violations
     */
    private function handleRuntimeSecurityViolations(array $violations): void
    {
        if ($violations === []) {
            return;
        }

        Log::critical('runtime.security_configuration_invalid', [
            'environment' => config('app.env'),
            'violations' => $violations,
            'app_url' => config('app.url'),
            'session_secure' => config('session.secure'),
            'db_connection' => config('database.default'),
            'db_sslmode' => config('database.connections.'.config('database.default').'.sslmode'),
            'order_pdfs_disk' => config('filesystems.order_pdfs_disk', 'private'),
            'tech_sheets_disk' => config('filesystems.tech_sheets_disk', 'private'),
        ]);

        if ($this->shouldEnforceRuntimeGuards()) {
            throw new RuntimeException(implode(' | ', $violations));
        }
    }

    private function shouldEnforceRuntimeGuards(): bool
    {
        return (bool) config('app.enforce_runtime_guards', false);
    }

    private function hasCanonicalAppUrl(string $appUrl): bool
    {
        $host = parse_url($appUrl, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return false;
        }

        $host = strtolower($host);

        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return false;
        }

        foreach (['.test', '.localhost', '.invalid', '.example', '.ngrok.io', '.ngrok-free.dev'] as $suffix) {
            if (str_ends_with($host, $suffix)) {
                return false;
            }
        }

        return true;
    }

    private function usesSecureDatabaseTransport(): bool
    {
        $defaultConnection = (string) config('database.default');
        $connection = config("database.connections.{$defaultConnection}", []);
        $driver = strtolower((string) ($connection['driver'] ?? ''));

        if ($driver !== 'pgsql') {
            return true;
        }

        $sslMode = strtolower((string) ($connection['sslmode'] ?? ''));

        return in_array($sslMode, ['require', 'verify-ca', 'verify-full'], true);
    }
}
