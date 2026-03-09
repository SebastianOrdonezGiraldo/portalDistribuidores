<?php

namespace App\Modules\Orders\Listeners;

use App\Modules\Orders\Events\OrderPlaced;
use App\Modules\Orders\Jobs\GenerateOrderPdfJob;

class GenerateOrderPdfListener
{
    public function handle(OrderPlaced $event): void
    {
        GenerateOrderPdfJob::dispatch($event->order->id);
    }
}

