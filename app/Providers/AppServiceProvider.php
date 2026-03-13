<?php

namespace App\Providers;

use App\Models\User;
use App\Modules\Admin\Policies\DistributorPolicy;
use App\Modules\Admin\Policies\UserPolicy;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Policies\ProductPolicy;
use App\Modules\Catalog\Queries\MeilisearchSearchEngine;
use App\Modules\Catalog\Queries\PostgresSearchEngine;
use App\Modules\Categories\Models\Category;
use App\Modules\Categories\Policies\CategoryPolicy;
use App\Modules\Documents\Policies\ProductDocumentPolicy;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Policies\OrderPolicy;
use App\Modules\Orders\Services\Cart\CartService;
use App\Modules\Shared\Contracts\SearchEngineInterface;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\Modules\Catalog\Models\ProductDocument;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SearchEngineInterface::class, function () {
            if (config('services.search.engine') === 'meilisearch') {
                return $this->app->make(MeilisearchSearchEngine::class);
            }

            return $this->app->make(PostgresSearchEngine::class);
        });
    }

    public function boot(): void
    {
        // Forzar HTTPS en producción (Railway usa proxy SSL)
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(ProductDocument::class, ProductDocumentPolicy::class);
        Gate::policy(Distributor::class, DistributorPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        View::composer('layouts.app', function ($view): void {
            $count = app(CartService::class)->count();
            $footerTopCategories = Category::query()
                ->where('is_active', true)
                ->withCount('products')
                ->orderByDesc('products_count')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->limit(4)
                ->get(['id', 'name']);

            $view->with([
                'navCartCount' => $count,
                'footerTopCategories' => $footerTopCategories,
            ]);
        });
    }
}
