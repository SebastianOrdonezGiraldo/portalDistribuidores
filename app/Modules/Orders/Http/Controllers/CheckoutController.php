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
        $items = $cartService->items();

        if ($items->isEmpty()) {
            return redirect()->route('catalog.index')->withErrors('Tu carrito está vacío.');
        }

        return view('orders.checkout', [
            'items' => $items,
            'total' => $cartService->total(),
        ]);
    }
}

