<?php

namespace App\Modules\Orders\Jobs;

use App\Modules\Orders\Mail\AbandonedCartReminderMail;
use App\Modules\Orders\Models\Cart;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendAbandonedCartReminderJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 4;

    public int $uniqueFor = 3600;

    public function __construct(
        public readonly int $cartId,
        public readonly int $expectedUpdatedAt,
    ) {}

    public function uniqueId(): string
    {
        return 'abandoned-cart-'.$this->cartId;
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(): void
    {
        $cart = Cart::query()
            ->with(['user', 'items.product'])
            ->find($this->cartId);

        if (! $this->isStillEligible($cart)) {
            return;
        }

        $claimed = DB::table('carts')
            ->where('id', $cart->id)
            ->whereNull('reminder_sent_at')
            ->where('updated_at', $cart->updated_at)
            ->update(['reminder_sent_at' => now()]);

        if ($claimed !== 1) {
            return;
        }

        try {
            Mail::to($cart->user->email)->send(new AbandonedCartReminderMail($cart));
        } catch (Throwable $exception) {
            DB::table('carts')
                ->where('id', $cart->id)
                ->where('updated_at', $cart->updated_at)
                ->update(['reminder_sent_at' => null]);

            throw $exception;
        }
    }

    private function isStillEligible(?Cart $cart): bool
    {
        if (! $cart || ! $cart->user || $cart->items->isEmpty()) {
            return false;
        }

        if (! $cart->user->isActive() || $cart->user->email_verified_at === null) {
            return false;
        }

        if (! filter_var($cart->user->email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if ($cart->reminder_sent_at !== null || $cart->updated_at->getTimestamp() !== $this->expectedUpdatedAt) {
            return false;
        }

        return $cart->updated_at->lte(now()->subHours(Cart::REMINDER_AFTER_HOURS))
            && ! $cart->hasExpired();
    }
}
