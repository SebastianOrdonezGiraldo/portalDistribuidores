<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Services\DashboardDataService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardDataService $dashboardData,
    ) {}

    public function __invoke(): View
    {
        return view('admin.dashboard', $this->dashboardData->getData());
    }
}
