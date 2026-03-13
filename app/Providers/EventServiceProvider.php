<?php

namespace App\Providers;

use App\Modules\Orders\Events\OrderPlaced;
use App\Modules\Orders\Listeners\GenerateOrderPdfListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderPlaced::class => [
            GenerateOrderPdfListener::class,
        ],
    ];
}
