<?php

namespace App\Providers;

use App\View\Composers\AdvisorContactComposer;
use App\View\Composers\AppLayoutComposer;
use App\View\Composers\GuestLayoutComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * View composers that share layout data across Blade views.
 */
class ViewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer('layouts.app', AppLayoutComposer::class);
        View::composer('layouts.guest', GuestLayoutComposer::class);
        View::composer([
            'catalog.index',
            'product.show',
            'cart.index',
            'orders.checkout',
        ], AdvisorContactComposer::class);
    }
}
