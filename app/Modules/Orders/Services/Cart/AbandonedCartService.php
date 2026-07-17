<?php

namespace App\Modules\Orders\Services\Cart;

use App\Modules\Orders\Jobs\SendAbandonedCartReminderJob;
use App\Modules\Orders\Models\Cart;

class AbandonedCartService
{
    /**
     * Queue one reminder during the final twelve hours and remove expired carts.
     *
     * @return array{reminders_queued:int, carts_deleted:int}
     */
    public function process(): array
    {
        $reminderCutoff = now()->subHours(Cart::REMINDER_AFTER_HOURS);
        $expirationCutoff = now()->subHours(Cart::EXPIRES_AFTER_HOURS);
        $queued = 0;

        Cart::query()
            ->whereNull('reminder_sent_at')
            ->where('updated_at', '<=', $reminderCutoff)
            ->where('updated_at', '>', $expirationCutoff)
            ->whereHas('items')
            ->whereHas('user', fn ($query) => $query
                ->where('is_active', true)
                ->whereNotNull('email_verified_at'))
            ->orderBy('id')
            ->chunkById(100, function ($carts) use (&$queued): void {
                foreach ($carts as $cart) {
                    SendAbandonedCartReminderJob::dispatch(
                        $cart->id,
                        $cart->updated_at->getTimestamp(),
                    );
                    $queued++;
                }
            });

        $deleted = Cart::query()
            ->where('updated_at', '<=', $expirationCutoff)
            ->delete();

        return [
            'reminders_queued' => $queued,
            'carts_deleted' => $deleted,
        ];
    }
}
