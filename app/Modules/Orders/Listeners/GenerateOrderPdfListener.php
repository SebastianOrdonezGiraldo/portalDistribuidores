<?php

namespace App\Modules\Orders\Listeners;

use App\Modules\Orders\Events\OrderPlaced;
use App\Modules\Orders\Jobs\GenerateOrderPdfJob;
use App\Modules\Orders\Jobs\SendOrderNotificationEmailJob;
use Illuminate\Support\Facades\Bus;

/**
 * Bridges the domain event for a submitted order into the async PDF/email chain.
 *
 * There is intentionally no separate email listener: the email job is chained
 * after PDF generation so a missing quotation attachment blocks notification.
 */
class GenerateOrderPdfListener
{
    /**
     * Queue PDF generation first, then notification email.
     */
    public function handle(OrderPlaced $event): void
    {
        // Chain garantiza que el email solo se envía DESPUÉS de que el PDF
        // se haya generado exitosamente. Si GenerateOrderPdfJob falla
        // todos sus reintentos, el chain se detiene y el email no se envía.
        Bus::chain([
            new GenerateOrderPdfJob($event->order->id),
            new SendOrderNotificationEmailJob($event->order->id),
        ])->dispatch();
    }
}
