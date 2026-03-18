<?php

namespace App\Modules\Orders\Listeners;

use App\Modules\Orders\Events\OrderPlaced;
use App\Modules\Orders\Jobs\GenerateOrderPdfJob;
use App\Modules\Orders\Jobs\SendOrderNotificationEmailJob;
use Illuminate\Support\Facades\Bus;

class GenerateOrderPdfListener
{
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
