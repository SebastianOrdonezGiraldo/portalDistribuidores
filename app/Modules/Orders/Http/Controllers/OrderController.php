<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Orders\Actions\CreateOrderAction;
use App\Modules\Orders\DTOs\CreateOrderData;
use App\Modules\Orders\Http\Requests\StoreOrderRequest;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\Cart\CartService;
use App\Modules\Orders\Services\OrderPdfGenerator;
use App\Modules\Shared\Exceptions\DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    private const GUEST_ORDERS_SESSION_KEY = 'orders.guest_access';

    public function store(
        StoreOrderRequest $request,
        CartService $cartService,
        CreateOrderAction $createOrderAction,
        OrderPdfGenerator $pdfGenerator,
    ): RedirectResponse {
        $items = $cartService->items()->map(fn (array $line) => [
            'product_id' => $line['product']->id,
            'variant_id' => $line['variant']?->id,
            'qty' => $line['qty'],
            'unit_label' => $line['unit_label'],
        ])->all();

        if ($items === []) {
            return redirect()->route('cart.index')->withErrors('No hay productos en el carrito.');
        }

        $data = CreateOrderData::fromArray(array_merge($request->validated(), ['items' => $items]));

        try {
            $order = $createOrderAction->execute($request->user(), $data);
        } catch (DomainException $exception) {
            return back()->withErrors($exception->getMessage())->withInput();
        }

        $this->rememberGuestOrder($order);
        $cartService->clear();

        $pdfPath = $pdfGenerator->generate($order);

        if ($order->pdf_path !== $pdfPath) {
            $order->update(['pdf_path' => $pdfPath]);
        }

        return redirect()
            ->route('orders.submitted', ['order' => $order, 'download_pdf' => 1])
            ->with('status', 'Orden creada correctamente. Descargando cotización en PDF.');
    }

    public function submitted(Order $order): View
    {
        abort_unless($this->canAccessOrder($order), 403);
        $order->loadMissing('items');

        return view('orders.submitted', ['order' => $order]);
    }

    public function show(Order $order): View
    {
        abort_unless($this->canAccessOrder($order), 403);
        $order->loadMissing('items', 'distributor', 'user');

        return view('orders.show', ['order' => $order]);
    }

    public function downloadPdf(Order $order, OrderPdfGenerator $pdfGenerator): StreamedResponse|RedirectResponse
    {
        abort_unless($this->canAccessOrder($order), 403);

        $path = $pdfGenerator->generate($order);

        if ($order->pdf_path !== $path) {
            $order->update(['pdf_path' => $path]);
        }

        if (! Storage::disk('public')->exists($path)) {
            return back()->withErrors('No fue posible generar el PDF de la cotización.');
        }

        return Storage::disk('public')->download($path, $order->oc_number.'.pdf');
    }

    private function canAccessOrder(Order $order): bool
    {
        $user = Auth::user();

        if ($user?->isAdmin()) {
            return true;
        }

        if ($user?->isDistributor() && $user->distributor_id === $order->distributor_id) {
            return true;
        }

        if ($user && $order->user_id === $user->id) {
            return true;
        }

        $guestOrderIds = collect(session(self::GUEST_ORDERS_SESSION_KEY, []))
            ->map(fn ($id) => (int) $id)
            ->all();

        return in_array((int) $order->id, $guestOrderIds, true);
    }

    private function rememberGuestOrder(Order $order): void
    {
        $guestOrderIds = collect(session(self::GUEST_ORDERS_SESSION_KEY, []))
            ->map(fn ($id) => (int) $id)
            ->push((int) $order->id)
            ->unique()
            ->values()
            ->all();

        session([self::GUEST_ORDERS_SESSION_KEY => $guestOrderIds]);
    }
}
