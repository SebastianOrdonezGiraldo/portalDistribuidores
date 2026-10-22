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
use App\Modules\Inventory\Services\ContaPymeInventoryService;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Policies\OrderPolicy;
use App\Modules\Orders\Pricing\CommercePricingRules;
use App\Modules\Orders\Pricing\CommercePricingRulesProvider;
use App\Modules\Orders\Pricing\DistributorPriceCalculator;
use App\Modules\Shared\Contracts\InventorySyncInterface;
use App\Modules\Shared\Contracts\SearchEngineInterface;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Scoutapm\Laravel\Providers\ScoutApmServiceProvider;

/**
 * Application composition root: container bindings, policies and gates.
 *
 * Domain rules should not accumulate here. Rate limiters live in
 * RateLimitServiceProvider, runtime guards in SecurityServiceProvider and
 * shared view data in ViewServiceProvider.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register swappable domain services and production-only infrastructure.
     */
    public function register(): void
    {
        $this->app->bind(SearchEngineInterface::class, PostgresSearchEngine::class);

        $this->app->singleton(InventorySyncInterface::class, ContaPymeInventoryService::class);

        // Fresh resolve on every injection so long-running workers never keep stale rules.
        $this->app->bind(
            CommercePricingRules::class,
            fn ($app) => $app->make(CommercePricingRulesProvider::class)->current(),
        );

        $this->app->bind(
            DistributorPriceCalculator::class,
            fn () => new DistributorPriceCalculator,
        );

        if (! app()->environment(['local', 'testing'])) {
            $this->app->register(ScoutApmServiceProvider::class);
        }
    }

    /**
     * Register model policies and cross-cutting gates.
     */
    public function boot(): void
    {
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
    }
}
