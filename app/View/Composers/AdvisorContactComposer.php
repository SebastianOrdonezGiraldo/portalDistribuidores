<?php

namespace App\View\Composers;

use App\Modules\Orders\Services\CommerceTierAdvisorProvider;
use Illuminate\View\View;

/**
 * Advisor and support contact for the shopping pages that show them.
 */
class AdvisorContactComposer
{
    public function compose(View $view): void
    {
        $view->with([
            'currentAdvisor' => app(CommerceTierAdvisorProvider::class)->forUser(auth()->user()),
            ...SupportContactData::forView(),
        ]);
    }
}
