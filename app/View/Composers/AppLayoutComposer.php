<?php

namespace App\View\Composers;

use App\Modules\Categories\Models\Category;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Pricing\DistributorTierResolver;
use App\Modules\Orders\Services\Cart\CartService;
use App\Modules\Orders\Services\CommerceTierAdvisorProvider;
use App\Modules\Shared\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Shared data for layouts.app: cart count, header/footer categories,
 * pending approvals badge, tier, advisor and support contact.
 */
class AppLayoutComposer
{
    public function compose(View $view): void
    {
        $count = app(CartService::class)->sessionCount();
        $pendingApprovalCount = auth()->user()?->isAdmin() && request()->routeIs('admin.dashboard')
            ? Order::query()->where('status', OrderStatus::PendingApproval)->count()
            : 0;

        $view->with([
            'navCartCount' => $count,
            'cartCount' => $count,
            'footerTopCategories' => $this->cached('footer_top_categories', fn () => Category::active()
                ->withCount('products')
                ->orderByDesc('products_count')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->limit(4)
                ->get(['id', 'name'])),
            'headerQuickCategories' => $this->cached('header_quick_categories', fn () => Category::active()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->limit(8)
                ->get(['id', 'name'])),
            'pendingApprovalCount' => $pendingApprovalCount,
            'distributorTier' => app(DistributorTierResolver::class)->resolve(auth()->user()),
            'currentAdvisor' => app(CommerceTierAdvisorProvider::class)->forUser(auth()->user()),
            ...SupportContactData::forView(),
        ]);
    }

    /**
     * In testing environment, skip caching to avoid stale data between tests.
     *
     * @param  callable(): Collection<int, Category>  $query
     * @return Collection<int, Category>
     */
    private function cached(string $key, callable $query): Collection
    {
        if (app()->environment('testing')) {
            return $query();
        }

        return Cache::remember($key, 3600, $query);
    }
}
