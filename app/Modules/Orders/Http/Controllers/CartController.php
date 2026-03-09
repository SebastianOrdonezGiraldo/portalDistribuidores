<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Http\Requests\AddToCartRequest;
use App\Modules\Orders\Http\Requests\UpdateCartRequest;
use App\Modules\Orders\Services\Cart\CartService;
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

    public function store(AddToCartRequest $request, CartService $cartService): RedirectResponse
    {
        $product = Product::query()->findOrFail($request->integer('product_id'));

        if (! $product->is_active) {
            return back()->withErrors('El producto no está disponible.');
        }

        $cartService->add(
            $product,
            $request->integer('qty', 1),
            $request->string('unit_label')->toString() ?: 'unidad',
        );

        return back()->with('status', 'Producto agregado al carrito.');
    }

    public function update(UpdateCartRequest $request, CartService $cartService): RedirectResponse
    {
        $cartService->update($request->input('quantities', []));

        return back()->with('status', 'Carrito actualizado.');
    }

    public function destroy(Product $product, CartService $cartService): RedirectResponse
    {
        $cartService->remove($product->id);

        return back()->with('status', 'Producto eliminado del carrito.');
    }
}
