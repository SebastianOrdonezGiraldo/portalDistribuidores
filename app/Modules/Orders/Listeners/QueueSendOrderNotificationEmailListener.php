<?php

namespace App\Modules\Orders\Listeners;

use App\Modules\Orders\Events\OrderPlaced;
use App\Modules\Orders\Jobs\SendOrderNotificationEmailJob;

/**
 * Este listener ya no se usa directamente.
 *
 * El envío del email de notificación ahora forma parte del chain de jobs
 * que despacha GenerateOrderPdfListener al recibir OrderPlaced:
 *   GenerateOrderPdfJob → SendOrderNotificationEmailJob
 *
 * Esto garantiza que el email se envía solo DESPUÉS de que el PDF esté listo,
 * eliminando la race condition que existía con el delay de 25 segundos.
 *
 * @deprecated Reemplazado por Bus::chain en GenerateOrderPdfListener
 */
class QueueSendOrderNotificationEmailListener
{
    public function handle(OrderPlaced $event): void
    {
        SendOrderNotificationEmailJob::dispatch($event->order->id)->delay(now()->addSeconds(25));
    }
}
