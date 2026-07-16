<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Orders\Http\Controllers\CartController;
use App\Modules\Orders\Services\Cart\CartService;
use App\Modules\Shared\Exceptions\DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Mostrar formulario de login.
     *
     * @group Autenticacion
     *
     * @unauthenticated
     *
     * @response 200 {"content":"Vista HTML del login"}
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Iniciar sesion.
     *
     * Autentica con sesion web y redirige segun el rol.
     *
     * @group Autenticacion
     *
     * @unauthenticated
     *
     * @bodyParam email string required Correo del usuario. Example: admin@importcorporal.test
     * @bodyParam password string required Contrasena del usuario. Example: secret
     *
     * @response 302 {"redirect":"catalog.index|dashboard"}
     * @response 422 {"message":"Credenciales invalidas o campos requeridos"}
     * @response 429 {"message":"Has realizado demasiados intentos."}
     */
    public function store(LoginRequest $request, CartService $cartService): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $pendingCartRedirect = $this->consumePendingCart($request, $cartService);

        if ($pendingCartRedirect) {
            return $pendingCartRedirect;
        }

        if ($request->user()?->isDistributor()) {
            return redirect()->route('catalog.index');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Cerrar sesion.
     *
     * @group Autenticacion
     *
     * @authenticated
     *
     * @response 302 {"redirect":"/"}
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function consumePendingCart(Request $request, CartService $cartService): ?RedirectResponse
    {
        $pendingCart = $request->session()->pull(CartController::PENDING_CART_SESSION_KEY);

        if (! is_array($pendingCart) || ! $request->user()?->isDistributor()) {
            return null;
        }

        $returnUrl = (string) ($pendingCart['return_url'] ?? route('catalog.index', absolute: false));
        $product = Product::query()->find((int) ($pendingCart['product_id'] ?? 0));

        if (! $product || ! $product->is_active) {
            return redirect($returnUrl)->withErrors('El producto ya no está disponible.');
        }

        $variant = null;
        $variantId = (int) ($pendingCart['variant_id'] ?? 0);

        if ($variantId > 0) {
            $variant = $product->variants()
                ->where('is_active', true)
                ->find($variantId);

            if (! $variant instanceof ProductVariant) {
                return redirect($returnUrl)->withErrors('La variante seleccionada ya no está disponible.');
            }
        }

        try {
            $cartService->add(
                $product,
                max(1, (int) ($pendingCart['qty'] ?? 1)),
                (string) ($pendingCart['unit_label'] ?? 'unidades'),
                $variant,
            );
        } catch (DomainException $exception) {
            return redirect($returnUrl)->withErrors($exception->getMessage());
        }

        return redirect($returnUrl)->with('status', 'Producto agregado al carrito.');
    }
}
