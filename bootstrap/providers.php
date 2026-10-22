<?php

use App\Providers\AppServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\RateLimitServiceProvider;
use App\Providers\SecurityServiceProvider;
use App\Providers\ViewServiceProvider;

return [
    AppServiceProvider::class,
    SecurityServiceProvider::class,
    RateLimitServiceProvider::class,
    ViewServiceProvider::class,
    EventServiceProvider::class,
];
