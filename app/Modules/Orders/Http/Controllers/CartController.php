<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Orders\Http\Requests\AddToCartRequest;
use App\Modules\Orders\Http\Requests\UpdateCartRequest;
use App\Modules\Orders\Services\Cart\CartService;
use App\Modules\Shared\Exceptions\DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(CartService $cartService): View
    {
        return view('cart.index', [
            'items' => $cartService->items(),
            'total' => $cartService->total(),
        ]);
    }

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

    public function update(UpdateCartRequest $request, CartService $cartService): RedirectResponse
    {
        try {
            $cartService->update($request->input('quantities', []));
        } catch (DomainException $exception) {
            return back()->withErrors($exception->getMessage());
        }

        if ($request->boolean('redirect_checkout')) {
            return redirect()->route('checkout.show');
        }

        return back()->with('status', 'Carrito actualizado.');
    }

    public function destroy(string $lineKey, CartService $cartService): RedirectResponse
    {
        if (preg_match('/^\d+-\d+$/', $lineKey) !== 1) {
            return back()->withErrors('Formato de línea de carrito inválido.');
        }

        $cartService->remove($lineKey);

        return back()->with('status', 'Línea eliminada del carrito.');
    }
}
