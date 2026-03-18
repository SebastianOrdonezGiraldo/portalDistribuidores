<?php

namespace App\Modules\Company\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Company\Http\Requests\RenameCompanyListRequest;
use App\Modules\Company\Http\Requests\StoreCompanyListRequest;
use App\Modules\Company\Models\CompanyList;
use App\Modules\Company\Models\CompanyListItem;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\Cart\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CompanyListController extends Controller
{
    public function index(): View
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        abort_unless($user->canManageLists(), 403);

        $lists = CompanyList::query()
            ->where('distributor_id', $user->distributor_id)
            ->withCount('items')
            ->with('creator')
            ->latest()
            ->get();

        return view('empresa.lists.index', compact('lists'));
    }

    public function storeFromCart(StoreCompanyListRequest $request, CartService $cartService): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        abort_unless($user->canManageLists(), 403);

        $cartItems = $cartService->items();

        if ($cartItems->isEmpty()) {
            return back()->with('warning', 'El carrito está vacío. Agrega productos antes de guardar la lista.');
        }

        $list = CompanyList::create([
            'distributor_id' => $user->distributor_id,
            'created_by'     => $user->id,
            'name'           => $request->validated('name'),
        ]);

        foreach ($cartItems as $item) {
            CompanyListItem::create([
                'list_id'               => $list->id,
                'product_id'            => $item['product']->id,
                'product_variant_id'    => $item['variant']?->id,
                'product_name_snapshot' => $item['product']->name,
                'sku_snapshot'          => $item['product']->sku,
                'variant_value_snapshot'=> $item['variant_label'],
                'qty'                   => max(1, (int) $item['qty']),
                'unit_label'            => $item['unit_label'] ?? 'unidad',
            ]);
        }

        return redirect()->route('empresa.lists.index')
            ->with('status', "Lista \"{$list->name}\" guardada con {$cartItems->count()} producto(s).");
    }

    public function storeFromOrder(StoreCompanyListRequest $request, Order $order): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        abort_unless($user->canManageLists(), 403);
        $this->authorize('view', $order);

        $order->loadMissing('items');

        if ($order->items->isEmpty()) {
            return back()->with('warning', 'Este pedido no tiene ítems para guardar como lista.');
        }

        $list = CompanyList::create([
            'distributor_id' => $user->distributor_id,
            'created_by'     => $user->id,
            'name'           => $request->validated('name'),
        ]);

        foreach ($order->items as $item) {
            CompanyListItem::create([
                'list_id'               => $list->id,
                'product_id'            => $item->product_id,
                'product_variant_id'    => $item->product_variant_id,
                'product_name_snapshot' => $item->product_name_snapshot,
                'sku_snapshot'          => $item->sku_snapshot,
                'variant_value_snapshot'=> $item->variant_value_snapshot,
                'qty'                   => max(1, (int) $item->qty),
                'unit_label'            => $item->unit_label ?? 'unidad',
            ]);
        }

        return redirect()->route('empresa.lists.index')
            ->with('status', "Lista \"{$list->name}\" guardada desde {$order->oc_number}.");
    }

    public function rename(RenameCompanyListRequest $request, CompanyList $list): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        abort_unless($user->canManageLists(), 403);
        $this->authorizeListBelongsToCompany($list);

        $list->update(['name' => $request->validated('name')]);

        return back()->with('status', 'Nombre de la lista actualizado.');
    }

    public function apply(CompanyList $list, CartService $cartService): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        abort_unless($user->canManageLists(), 403);
        $this->authorizeListBelongsToCompany($list);

        $list->loadMissing('items');

        if ($list->items->isEmpty()) {
            return back()->with('warning', 'La lista está vacía.');
        }

        $productIds = $list->items->pluck('product_id')->filter()->unique()->values()->all();
        $variantIds = $list->items->pluck('product_variant_id')->filter()->unique()->values()->all();

        $activeProducts = Product::active()->whereIn('id', $productIds)->get()->keyBy('id');
        $activeVariants = $variantIds
            ? ProductVariant::active()->whereIn('id', $variantIds)->get()->keyBy('id')
            : collect();

        $added   = 0;
        $skipped = [];

        foreach ($list->items as $item) {
            $product = $activeProducts->get($item->product_id);

            if (! $product) {
                $skipped[] = $item->product_name_snapshot.' (no disponible)';
                continue;
            }

            $variant = null;

            if ($item->product_variant_id) {
                $variant = $activeVariants->get($item->product_variant_id);

                if (! $variant) {
                    $skipped[] = $item->product_name_snapshot.' (variante no disponible)';
                    continue;
                }
            } elseif ($product->hasConfigurableVariants()) {
                $skipped[] = $item->product_name_snapshot.' (requiere elegir variante)';
                continue;
            }

            $cartService->add(
                product: $product,
                qty: max(1, (int) $item->qty),
                unitLabel: $item->unit_label ?? 'unidad',
                variant: $variant,
            );

            $added++;
        }

        if ($added === 0) {
            return redirect()->route('catalog.index')
                ->with('warning', 'No se pudo agregar ningún producto de la lista al carrito.');
        }

        $statusMsg = "Lista \"{$list->name}\" aplicada: {$added} producto(s) agregados al carrito.";

        if (! empty($skipped)) {
            return redirect()->route('cart.index')
                ->with('status', $statusMsg)
                ->with('warning', 'No se pudieron agregar: '.implode(', ', $skipped));
        }

        return redirect()->route('cart.index')->with('status', $statusMsg);
    }

    public function destroy(CompanyList $list): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        abort_unless($user->canManageLists(), 403);
        $this->authorizeListBelongsToCompany($list);

        $name = $list->name;
        $list->delete();

        return redirect()->route('empresa.lists.index')
            ->with('status', "Lista \"{$name}\" eliminada.");
    }

    private function authorizeListBelongsToCompany(CompanyList $list): void
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        abort_unless(
            (int) $list->distributor_id === (int) $user->distributor_id,
            403,
            'No tienes acceso a esta lista.'
        );
    }
}
