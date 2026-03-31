<?php

namespace App\Modules\Company\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Company\Http\Requests\RejectOrderRequest;
use App\Modules\Orders\Events\OrderPlaced;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\OrderStatusTransitionService;
use App\Modules\Shared\Enums\OrderStatus;
use App\Modules\Shared\Exceptions\DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CompanyApprovalController extends Controller
{
    public function index(): View
    {
        $this->authorize('approveOrders');

        /** @var \App\Models\User $user */
        $user = auth()->user();

        $pending = Order::query()
            ->where('distributor_id', $user->distributor_id)
            ->where('status', OrderStatus::PendingApproval)
            ->with('user')
            ->latest()
            ->get();

        $rejected = Order::query()
            ->where('distributor_id', $user->distributor_id)
            ->where('status', OrderStatus::Rejected)
            ->with('user')
            ->latest()
            ->take(10)
            ->get();

        return view('empresa.approvals.index', compact('pending', 'rejected'));
    }

    public function approve(
        Order $order,
        OrderStatusTransitionService $transitionService,
    ): RedirectResponse
    {
        $this->authorize('approveOrders');
        $this->authorizeOrderBelongsToCompany($order);

        abort_unless($order->status->canBeApproved(), 422, 'Este pedido no puede ser aprobado en su estado actual.');

        /** @var \App\Models\User $actor */
        $actor = auth()->user();

        try {
            $order = $transitionService->transition(
                $order,
                OrderStatus::Submitted,
                $actor,
                'Aprobación interna de empresa.'
            );
        } catch (DomainException $exception) {
            return back()->withErrors($exception->getMessage());
        }

        $order->update([
            'approval_note' => null,
        ]);

        // Disparar el evento que genera el PDF y notificaciones
        event(new OrderPlaced($order));

        return redirect()->route('empresa.approvals.index')
            ->with('status', "Pedido {$order->oc_number} aprobado. Se está procesando la cotización.");
    }

    public function reject(
        RejectOrderRequest $request,
        Order $order,
        OrderStatusTransitionService $transitionService,
    ): RedirectResponse
    {
        $this->authorize('approveOrders');
        $this->authorizeOrderBelongsToCompany($order);

        abort_unless($order->status->canBeApproved(), 422, 'Este pedido no puede ser rechazado en su estado actual.');

        /** @var \App\Models\User $actor */
        $actor = auth()->user();
        $note = $request->validated('approval_note');

        try {
            $order = $transitionService->transition(
                $order,
                OrderStatus::Rejected,
                $actor,
                $note
            );
        } catch (DomainException $exception) {
            return back()->withErrors($exception->getMessage());
        }

        $order->update([
            'approval_note' => $note,
        ]);

        return redirect()->route('empresa.approvals.index')
            ->with('status', "Pedido {$order->oc_number} rechazado.");
    }

    private function authorizeOrderBelongsToCompany(Order $order): void
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        abort_unless(
            (int) $order->distributor_id === (int) $user->distributor_id,
            403,
            'No tienes acceso a este pedido.'
        );
    }
}
