<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Orders\Http\Requests\AddToCartRequest;
use App\Modules\Orders\Http\Requests\UpdateCartRequest;
use App\Modules\Orders\Services\Cart\CartService;
use App\Modules\Orders\Support\CartLiveUpdatePresenter;
use App\Modules\Shared\Exceptions\DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CartController extends Controller
{
    public const PENDING_CART_SESSION_KEY = 'orders.pending_cart_after_login';

    /**
     * Ver carrito.
     *
     * @group Carrito y pedidos
     *
     * @unauthenticated
     *
     * @response 200 {"content":"Vista HTML del carrito"}
     */
    public function index(CartService $cartService): View
    {
        return view('cart.index', [
            'items' => $cartService->items(),
            'total' => $cartService->total(),
            'pricing' => $cartService->pricingResult(),
        ]);
    }

    /**
     * Agregar producto al carrito.
     *
     * Puede responder JSON para solicitudes AJAX o redirect para formularios web.
     *
     * @group Carrito y pedidos
     *
     * @unauthenticated
     *
     * @bodyParam product_id integer required ID del producto. Example: 10
     * @bodyParam variant_id integer ID de variante activa cuando el producto tiene variantes. Example: 30
     * @bodyParam qty integer required Cantidad. Example: 2
     * @bodyParam unit_label string Unidad mostrada. Example: unidades
     *
     * @response 200 {"ok":true,"message":"Producto agregado al carrito.","cart_count":1}
     * @response 302 {"redirect":"back"}
     * @response 422 {"ok":false,"message":"El producto no esta disponible."}
     * @response 429 {"message":"Has realizado demasiados intentos."}
     */
    public function store(AddToCartRequest $request, CartService $cartService): JsonResponse|RedirectResponse
    {
        $product = Product::query()->findOrFail($request->integer('product_id'));

        if (! $product->is_active) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'El producto no está disponible.',
                ], 422);
            }

            return back()->withErrors('El producto no está disponible.');
        }

        if (! $request->user() && $request->expectsJson()) {
            $returnUrl = route('products.show', $product, absolute: false);

            $request->session()->put(self::PENDING_CART_SESSION_KEY, [
                'product_id' => $product->id,
                'variant_id' => $request->integer('variant_id') ?: null,
                'qty' => $request->integer('qty', 1),
                'unit_label' => $request->string('unit_label')->toString() ?: 'unidades',
                'return_url' => $returnUrl,
            ]);
            $request->session()->put('url.intended', $returnUrl);

            return response()->json([
                'ok' => false,
                'requires_login' => true,
                'message' => 'Inicia sesión para agregar productos al carrito.',
                'login_url' => route('login', absolute: false),
                'return_url' => $returnUrl,
            ], 401);
        }

        $variant = null;
        $activeVariants = $product->variants()
            ->where('is_active', true)
            ->with('attributeValue')
            ->get();

        if ($activeVariants->isNotEmpty()) {
            $variant = $activeVariants->firstWhere('id', $request->integer('variant_id'));

            if (! $variant instanceof ProductVariant) {
                $message = 'Debes seleccionar una variante válida.';

                if ($request->expectsJson()) {
                    return response()->json([
                        'ok' => false,
                        'message' => $message,
                    ], 422);
                }

                return back()->withErrors($message);
            }
        }

        try {
            $cartService->add(
                $product,
                $request->integer('qty', 1),
                $request->string('unit_label')->toString() ?: 'unidades',
                $variant,
            );
        } catch (DomainException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => $exception->getMessage(),
                ], 422);
            }

            return back()->withErrors($exception->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Producto agregado al carrito.',
                'cart_count' => $cartService->count(),
            ]);
        }

        return back()->with('status', 'Producto agregado al carrito.');
    }

    /**
     * Actualizar cantidades del carrito.
     *
     * Responde JSON para autosync del carrito o redirect para formularios HTML.
     *
     * @group Carrito y pedidos
     *
     * @unauthenticated
     *
     * @bodyParam quantities object required Mapa lineKey => cantidad. Example: {"10-0":2}
     * @bodyParam redirect_checkout boolean Redirige al checkout despues de actualizar. Example: true
     *
     * @response 200 {"ok":true,"empty":false,"pricing":{},"lines":{},"html":{}}
     * @response 302 {"redirect":"back|checkout"}
     * @response 422 {"ok":false,"message":"...","state":{}}
     * @response 429 {"message":"Has realizado demasiados intentos."}
     */
    public function update(
        UpdateCartRequest $request,
        CartService $cartService,
        CartLiveUpdatePresenter $presenter,
    ): JsonResponse|RedirectResponse {
        $wantsJson = $request->expectsJson();

        try {
            $cartService->update($request->input('quantities', []));
        } catch (DomainException $exception) {
            if ($wantsJson) {
                return $this->jsonCanonicalState(
                    $cartService,
                    $presenter,
                    ok: false,
                    message: $exception->getMessage(),
                    status: 422,
                );
            }

            return back()->withErrors($exception->getMessage());
        }

        if ($wantsJson) {
            $items = $cartService->items();
            $pricing = $cartService->pricingResult();

            if ($items->isEmpty() || $pricing === null) {
                return response()->json([
                    'ok' => true,
                    'empty' => true,
                    'redirect_url' => route('cart.index', absolute: false),
                ]);
            }

            return response()->json($presenter->present($items, $pricing));
        }

        if ($request->boolean('redirect_checkout')) {
            return redirect()->route('checkout.show');
        }

        return back()->with('status', 'Carrito actualizado.');
    }

    /**
     * Eliminar linea del carrito.
     *
     * @group Carrito y pedidos
     *
     * @unauthenticated
     *
     * @urlParam lineKey string required Identificador interno de linea `producto-variante`. Example: 10-0
     *
     * @response 302 {"redirect":"back"}
     * @response 422 {"message":"Formato de linea de carrito invalido."}
     * @response 429 {"message":"Has realizado demasiados intentos."}
     */
    public function destroy(string $lineKey, CartService $cartService): RedirectResponse
    {
        if (preg_match('/^\d+-\d+$/', $lineKey) !== 1) {
            return back()->withErrors('Formato de línea de carrito inválido.');
        }

        $cartService->remove($lineKey);

        return back()->with('status', 'Línea eliminada del carrito.');
    }

    private function jsonCanonicalState(
        CartService $cartService,
        CartLiveUpdatePresenter $presenter,
        bool $ok,
        string $message,
        int $status,
    ): JsonResponse {
        $items = $cartService->items();
        $pricing = $cartService->pricingResult();

        if ($items->isEmpty() || $pricing === null) {
            return response()->json([
                'ok' => $ok,
                'message' => $message,
                'state' => [
                    'ok' => true,
                    'empty' => true,
                    'redirect_url' => route('cart.index', absolute: false),
                ],
            ], $status);
        }

        return response()->json([
            'ok' => $ok,
            'message' => $message,
            'state' => $presenter->present($items, $pricing),
        ], $status);
    }
}
