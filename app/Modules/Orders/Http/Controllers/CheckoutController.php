<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Orders\Services\Cart\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __invoke(CartService $cartService): View|RedirectResponse
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        if ($user && ! $user->canCreateOrders()) {
            return redirect()->route('empresa.dashboard')
                ->withErrors('Tu rol de empresa no permite crear pedidos.');
        }

        $items = $cartService->items();

        if ($items->isEmpty()) {
            return redirect()->route('catalog.index')->withErrors('Tu carrito está vacío.');
        }

        $distributor = $user?->distributor;
        $branches    = $distributor
            ? $distributor->branches()->orderByDesc('is_default')->orderBy('name')->get()
            : collect();

        return view('orders.checkout', [
            'items'       => $items,
            'total'       => $cartService->total(),
            'distributor' => $distributor,
            'branches'    => $branches,
            'departments' => config('locations.colombia_departments', []),
        ]);
    }
}
