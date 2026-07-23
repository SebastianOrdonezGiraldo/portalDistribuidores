<?php

namespace App\Modules\Orders\Services\Payment;

use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\PaymentUploadToken;
use Illuminate\Support\Facades\DB;

/**
 * Issues and resolves hashed magic-link tokens for cross-device receipt upload.
 */
class PaymentUploadTokenService
{
    public function issue(Order $order): string
    {
        $plainToken = bin2hex(random_bytes(32));
        $ttlMinutes = max(5, (int) config('commerce.payment.upload_token_ttl_minutes', 20));

        PaymentUploadToken::query()->create([
            'order_id' => $order->id,
            'token_hash' => $this->hash($plainToken),
            'expires_at' => now()->addMinutes($ttlMinutes),
            'consumed_at' => null,
        ]);

        return $plainToken;
    }

    public function findUsableForOrder(Order $order, string $plainToken): ?PaymentUploadToken
    {
        $hash = $this->hash($plainToken);

        /** @var PaymentUploadToken|null $token */
        $token = PaymentUploadToken::query()
            ->where('order_id', $order->id)
            ->where('token_hash', $hash)
            ->first();

        if (! $token || ! $token->isUsable()) {
            return null;
        }

        return $token;
    }

    /**
     * Look up by hash only (no order scope). Used for generic 404 when mismatch.
     */
    public function findByPlainToken(string $plainToken): ?PaymentUploadToken
    {
        return PaymentUploadToken::query()
            ->where('token_hash', $this->hash($plainToken))
            ->first();
    }

    public function markConsumed(PaymentUploadToken $token): void
    {
        $token->update(['consumed_at' => now()]);
    }

    /**
     * Invalidate active tokens and issue a fresh one (e.g. after expiry UI regenerate).
     */
    public function regenerate(Order $order): string
    {
        return DB::transaction(function () use ($order): string {
            PaymentUploadToken::query()
                ->where('order_id', $order->id)
                ->whereNull('consumed_at')
                ->where('expires_at', '>', now())
                ->update(['consumed_at' => now()]);

            return $this->issue($order);
        });
    }

    public function uploadUrl(Order $order, string $plainToken): string
    {
        return route('orders.payment-receipt.show', [
            'order' => $order,
            'token' => $plainToken,
        ]);
    }

    private function hash(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }
}
