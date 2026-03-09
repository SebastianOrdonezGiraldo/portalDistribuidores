<?php

namespace App\Modules\Orders\Listeners;

use App\Modules\Orders\Events\OrderPlaced;
use App\Modules\Orders\Jobs\SendOrderNotificationEmailJob;

class QueueSendOrderNotificationEmailListener
{
    public function handle(OrderPlaced $event): void
    {
        SendOrderNotificationEmailJob::dispatch($event->order->id)->delay(now()->addSeconds(25));
    }
}

