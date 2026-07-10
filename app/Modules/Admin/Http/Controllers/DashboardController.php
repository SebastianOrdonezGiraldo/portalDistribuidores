<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Services\DashboardDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const SYNC_MUTEX_KEY = 'contapyme_sync_running';

    private const SYNC_MUTEX_TTL = 600;

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
        return view('admin.dashboard', array_merge(
            $this->dashboardData->getData(),
            ['syncRunning' => Cache::has(self::SYNC_MUTEX_KEY)],
        ));
    }

    /**
     * Ejecutar sincronización de stock desde ContaPyme manualmente.
     *
     * @group Admin
     *
     * @authenticated
     */
    public function syncStock(): RedirectResponse
    {
        if (Cache::has(self::SYNC_MUTEX_KEY)) {
            return redirect()
                ->route('admin.dashboard')
                ->with('error', 'Ya hay una sincronización en curso. Espera a que termine.');
        }

        Cache::set(self::SYNC_MUTEX_KEY, true, self::SYNC_MUTEX_TTL);

        try {
            set_time_limit(120);

            $exitCode = Artisan::call('contapyme:sync-stock');
            $output = Artisan::output();
            $lastLine = last(array_filter(explode("\n", trim($output))));

            Cache::forget('admin.dashboard.metrics');

            if ($exitCode === 0) {
                return redirect()
                    ->route('admin.dashboard')
                    ->with('success', 'Sincronización ContaPyme completada. '.$lastLine);
            }

            return redirect()
                ->route('admin.dashboard')
                ->with('error', 'Error en ContaPyme: '.$lastLine);
        } catch (\Throwable $e) {
            return redirect()
                ->route('admin.dashboard')
                ->with('error', 'Error inesperado: '.$e->getMessage());
        } finally {
            Cache::forget(self::SYNC_MUTEX_KEY);
        }
    }
}
