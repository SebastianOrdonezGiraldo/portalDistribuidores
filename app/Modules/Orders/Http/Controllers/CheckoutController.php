<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Orders\Pricing\TierPrice;
use App\Modules\Orders\Services\Cart\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    /**
     * Mostrar checkout.
     *
     * Requiere carrito con items. Usuarios empresa con rol sin permisos de compra son redirigidos.
     *
     * @group Carrito y pedidos
     *
     * @unauthenticated
     *
     * @response 200 {"content":"Vista HTML de checkout"}
     * @response 302 {"redirect":"catalog.index|empresa.dashboard"}
     */
    public function __invoke(CartService $cartService): View|RedirectResponse
    {
        /** @var User|null $user */
        $user = auth()->user();

        if ($user && ! $user->canCreateOrders()) {
            return redirect()->route('empresa.dashboard')
                ->withErrors('Tu rol de empresa no permite crear pedidos.');
        }

        $items = $cartService->items();

        if ($items->isEmpty()) {
            return redirect()->route('catalog.index')->withErrors('Tu carrito está vacío.');
        }

        $pricing = $cartService->pricingResult();

        if ($pricing !== null && ! $pricing->checkoutAllowed()) {
            $missing = TierPrice::centsToDecimal($pricing->minimumOrderMissingAmountCents());
            $minimum = TierPrice::centsToDecimal($pricing->minimumOrderAmountCents());
            $tierLabel = $pricing->minimumOrderDecision->tier->badgeLabel();
            [$missingWhole] = array_pad(explode('.', $missing, 2), 2, '0');
            [$minimumWhole] = array_pad(explode('.', $minimum, 2), 2, '0');

            return redirect()->route('cart.index')->withErrors(
                'Pedido mínimo para '.$tierLabel.': $'.number_format((int) $minimumWhole, 0, ',', '.').'. Te faltan $'.number_format((int) $missingWhole, 0, ',', '.').'.'
            );
        }

        $distributor = $user?->distributor;
        $branches = $distributor
            ? $distributor->branches()->orderByDesc('is_default')->orderBy('name')->get()
            : collect();

        return view('orders.checkout', [
            'items' => $items,
            'total' => $cartService->total(),
            'pricing' => $pricing,
            'distributor' => $distributor,
            'branches' => $branches,
            'departments' => config('locations.colombia_departments', []),
        ]);
    }
}
