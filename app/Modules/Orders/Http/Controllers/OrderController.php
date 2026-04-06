<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Orders\Actions\CreateOrderAction;
use App\Modules\Orders\DTOs\CreateOrderData;
use App\Modules\Orders\Http\Requests\StoreOrderRequest;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\Cart\CartService;
use App\Modules\Orders\Services\OrderPdfGenerator;
use App\Modules\Shared\Exceptions\DomainException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    private const GUEST_ORDERS_SESSION_KEY = 'orders.guest_access';
    private const SESSION_EXPIRED_MESSAGE = 'Tu sesión expiró o ya no es válida. Inicia sesión para continuar con tu pedido.';

    public function store(
        StoreOrderRequest $request,
        CartService $cartService,
        CreateOrderAction $createOrderAction,
    ): RedirectResponse {
        /** @var User|null $user */
        $user = $request->user();

        if ($user && ! $user->canCreateOrders()) {
            return redirect()->route('empresa.dashboard')
                ->withErrors('Tu rol de empresa no permite crear pedidos.');
        }

        $requiresApproval = $user !== null && $user->orderRequiresApproval();

        $items = $cartService->items()->map(fn (array $line) => [
            'product_id' => $line['product']->id,
            'variant_id' => $line['variant']?->id,
            'qty' => $line['qty'],
            'unit_label' => $line['unit_label'],
        ])->all();

        if ($items === []) {
            return redirect()->route('cart.index')->withErrors('No hay productos en el carrito.');
        }

        $data = CreateOrderData::fromArray(array_merge($request->validated(), [
            'items' => $items,
            'requires_approval' => $requiresApproval,
        ]));

        try {
            $order = $createOrderAction->execute($request->user(), $data);
        } catch (DomainException $exception) {
            return back()->withErrors($exception->getMessage())->withInput();
        }

        $this->rememberGuestOrder($order);
        $cartService->clear();

        if ($requiresApproval) {
            return redirect()
                ->route('empresa.orders.show', $order)
                ->with('status', 'Solicitud enviada. Queda pendiente de aprobación por el administrador de tu empresa.');
        }

        // El PDF y el email de notificación se gestionan vía el evento OrderPlaced
        // que dispara CreateOrderAction. Ver GenerateOrderPdfListener.

        return redirect()
            ->route('orders.submitted', ['order' => $order])
            ->with('status', 'Orden creada correctamente. Estamos procesando la cotización.');
    }

    public function submitted(Order $order): View|RedirectResponse
    {
        if (! $this->canAccessOrder($order)) {
            return $this->unauthorizedOrderAccessResponse();
        }

        $order->loadMissing('items');

        return view('orders.submitted', ['order' => $order]);
    }

    public function show(Order $order): View|RedirectResponse
    {
        if (! $this->canAccessOrder($order)) {
            return $this->unauthorizedOrderAccessResponse();
        }

        $order->loadMissing('items', 'distributor', 'user', 'statusHistory.actor');

        return view('orders.show', ['order' => $order]);
    }

    public function downloadPdf(Order $order, OrderPdfGenerator $pdfGenerator): StreamedResponse|RedirectResponse
    {
        if (! $this->canAccessOrder($order)) {
            return $this->unauthorizedOrderAccessResponse();
        }

        $path = $pdfGenerator->generate($order);

        if ($order->pdf_path !== $path) {
            $order->update(['pdf_path' => $path]);
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk(OrderPdfGenerator::diskName());

        if (! $disk->exists($path)) {
            return back()->withErrors('No fue posible generar el PDF de la cotización.');
        }

        return $disk->download($path, $order->oc_number.'.pdf');
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

    private function unauthorizedOrderAccessResponse(): RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->guest(route('login'))->with('status', self::SESSION_EXPIRED_MESSAGE);
        }

        abort(403);
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
