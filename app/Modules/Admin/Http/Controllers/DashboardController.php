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

    /**
     * Ver dashboard administrativo.
     *
     * @group Admin
     *
     * @authenticated
     *
     * @response 200 {"content":"Vista HTML con KPIs administrativos"}
     * @response 403 {"message":"No autorizado"}
     */
    public function __invoke(): View
    {
        return view('admin.dashboard', $this->dashboardData->getData());
    }
}
