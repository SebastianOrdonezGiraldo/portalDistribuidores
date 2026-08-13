<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Support\PaymentReceiptUploadLimits;
use App\Modules\Orders\Services\Payment\OrderPaymentService;
use App\Modules\Orders\Services\Payment\PaymentReceiptUploadService;
use App\Modules\Orders\Services\Payment\PaymentUploadTokenService;
use App\Modules\Shared\Enums\PaymentStatus;
use App\Modules\Shared\Exceptions\DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public magic-link flow for uploading a payment receipt from another device.
 */
class PaymentReceiptController extends Controller
{
    public function show(
        Request $request,
        Order $order,
        PaymentUploadTokenService $tokenService,
    ): View|Response {
        $plainToken = (string) $request->query('token', '');

        if ($plainToken === '') {
            abort(404);
        }

        $tokenRow = $tokenService->findByPlainToken($plainToken);

        // Generic 404 when token missing or does not belong to this order (no enumeration).
        if (! $tokenRow || (int) $tokenRow->order_id !== (int) $order->id) {
            abort(404);
        }

        if ($tokenRow->isExpired()) {
            return view('orders.payment-receipt', [
                'order' => $order,
                'state' => 'token_expired',
                'plainToken' => null,
                'canRegenerate' => Auth::check() || $this->hasGuestAccess($order),
                'maxSizeLabel' => PaymentReceiptUploadLimits::maxSizeLabel(),
            ]);
        }

        if ($tokenRow->isConsumed() || ! $order->payment_status->allowsReceiptUpload()) {
            return view('orders.payment-receipt', [
                'order' => $order,
                'state' => 'token_consumed_or_closed',
                'plainToken' => null,
                'canRegenerate' => false,
                'maxSizeLabel' => PaymentReceiptUploadLimits::maxSizeLabel(),
            ]);
        }

        return view('orders.payment-receipt', [
            'order' => $order,
            'state' => 'ready',
            'plainToken' => $plainToken,
            'canRegenerate' => false,
            'maxSizeLabel' => PaymentReceiptUploadLimits::maxSizeLabel(),
        ]);
    }

    public function store(
        Request $request,
        Order $order,
        PaymentUploadTokenService $tokenService,
        PaymentReceiptUploadService $uploadService,
    ): RedirectResponse {
        $plainToken = (string) $request->input('token', $request->query('token', ''));

        if ($plainToken === '') {
            abort(404);
        }

        $tokenRow = $tokenService->findByPlainToken($plainToken);

        if (! $tokenRow || (int) $tokenRow->order_id !== (int) $order->id) {
            abort(404);
        }

        if ($tokenRow->isExpired()) {
            return back()->withErrors('El enlace expiró. Solicita uno nuevo desde el detalle del pedido.');
        }

        if ($tokenRow->isConsumed() || ! $order->payment_status->allowsReceiptUpload()) {
            return redirect()
                ->route('orders.payment-receipt.show', ['order' => $order, 'token' => $plainToken])
                ->with('status', 'Este enlace ya no admite más subidas. Revisa el estado actual del pedido.');
        }

        $request->validate([
            'receipt' => [
                'required',
                'file',
                'max:'.PaymentReceiptUploadLimits::maxSizeKb(),
                'mimes:jpg,jpeg,png,gif,webp,pdf',
            ],
        ], [
            'receipt.required' => 'Adjunta el comprobante de pago.',
            'receipt.mimes' => 'El comprobante debe ser imagen (JPG, PNG, WEBP) o PDF.',
            'receipt.max' => 'El comprobante no puede superar '.PaymentReceiptUploadLimits::maxSizeLabel().'.',
        ]);

        try {
            $uploadService->upload($order, $request->file('receipt'), $tokenRow);
        } catch (DomainException $exception) {
            return back()->withErrors($exception->getMessage());
        }

        return redirect()
            ->route('orders.payment-receipt.show', ['order' => $order, 'token' => $plainToken])
            ->with('status', 'Comprobante recibido. Estamos confirmando tu pago.');
    }

    public function regenerate(
        Request $request,
        Order $order,
        PaymentUploadTokenService $tokenService,
        OrderPaymentService $paymentService,
    ): RedirectResponse {
        if (! Auth::check() && ! $this->hasGuestAccess($order)) {
            return redirect()->guest(route('login'))
                ->with('status', 'Inicia sesión para generar un nuevo enlace de comprobante.');
        }

        if (! $order->payment_status->allowsReceiptUpload()) {
            return back()->withErrors('Este pedido ya no acepta comprobantes de pago.');
        }

        // Ensure reservation window still open or refresh TTL lightly for regenerate.
        if ($order->payment_reservation_expires_at === null
            || $order->payment_reservation_expires_at->isPast()) {
            $order->update([
                'payment_reservation_expires_at' => now()->addMinutes($paymentService->reservationTtlMinutes()),
                'payment_status' => $order->payment_status === PaymentStatus::Rejected
                    ? PaymentStatus::Rejected
                    : PaymentStatus::PendingUpload,
            ]);
        }

        $plain = $tokenService->regenerate($order);

        return redirect()
            ->route('orders.payment-receipt.show', ['order' => $order, 'token' => $plain])
            ->with('status', 'Nuevo enlace generado. Úsalo desde tu celular para subir el comprobante.');
    }

    private function hasGuestAccess(Order $order): bool
    {
        $guestOrderIds = collect(session('orders.guest_access', []))
            ->map(fn ($id) => (int) $id)
            ->all();

        return in_array((int) $order->id, $guestOrderIds, true);
    }
}
