<?php

namespace App\Providers;

use App\Models\User;
use App\Modules\Admin\Policies\DistributorPolicy;
use App\Modules\Admin\Policies\UserPolicy;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Company\Policies\CompanyPolicy;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Policies\ProductPolicy;
use App\Modules\Catalog\Queries\PostgresSearchEngine;
use App\Modules\Categories\Models\Category;
use App\Modules\Categories\Policies\CategoryPolicy;
use App\Modules\Catalog\Models\ProductDocument;
use App\Modules\Documents\Policies\ProductDocumentPolicy;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Policies\OrderPolicy;
use App\Modules\Orders\Services\Cart\CartService;
use App\Modules\Shared\Contracts\SearchEngineInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SearchEngineInterface::class, PostgresSearchEngine::class);
    }

    public function boot(): void
    {
        // Forzar HTTPS en producción (Railway usa proxy SSL)
        if (app()->isProduction()) {
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
        Gate::define('manageUsers', function (User $user) {
            if ($user->isAdmin()) {
                return true;
            }
            return (new CompanyPolicy)->manageUsers($user);
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
            $count = app(CartService::class)->count();
            $footerTopCategories = Cache::remember('footer_top_categories', 3600, fn () => Category::active()
                ->withCount('products')
                ->orderByDesc('products_count')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->limit(4)
                ->get(['id', 'name'])
            );

            $view->with([
                'navCartCount'        => $count,
                'cartCount'           => $count,
                'footerTopCategories' => $footerTopCategories,
            ]);
        });
    }
}
