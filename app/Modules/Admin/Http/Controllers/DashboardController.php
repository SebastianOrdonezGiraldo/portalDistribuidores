<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Services\DashboardDataService;
use App\Modules\Inventory\Jobs\SyncContaPymeStockJob;
use App\Modules\Inventory\Services\ContaPymeSyncState;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
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
    public function __invoke(ContaPymeSyncState $syncState): View
    {
        return view('admin.dashboard', array_merge(
            $this->dashboardData->getData(),
            ['syncRunning' => $syncState->isRunning()],
        ));
    }

    /**
     * Ejecutar sincronización de stock desde ContaPyme manualmente.
     *
     * @group Admin
     *
     * @authenticated
     */
    public function syncStock(Request $request, ContaPymeSyncState $syncState): RedirectResponse
    {
        if ($syncState->isRunning()) {
            return $this->redirectToProducts($request)
                ->with('error', 'Ya hay una sincronización en curso. Espera a que termine.');
        }

        if (! (bool) config('contapyme.enabled')) {
            $message = 'La sincronización ContaPyme está deshabilitada en este entorno.';
            $syncState->block($message);

            return $this->redirectToProducts($request)->with('error', $message);
        }

        if (! $syncState->queue()) {
            return $this->redirectToProducts($request)
                ->with('error', 'Ya hay una sincronización en curso. Espera a que termine.');
        }

        try {
            Bus::dispatch(new SyncContaPymeStockJob);
        } catch (\Throwable $e) {
            $syncState->fail('No fue posible encolar la sincronización ContaPyme.');

            return $this->redirectToProducts($request)
                ->with('error', 'No fue posible encolar la sincronización ContaPyme.');
        }

        return $this->redirectToProducts($request)
            ->with('success', 'Sincronización ContaPyme encolada. El resultado aparecerá al recargar el catálogo.');
    }

    private function redirectToProducts(Request $request): RedirectResponse
    {
        return redirect()->route('admin.products.index', $request->only([
            'q',
            'category_id',
            'status',
            'media',
            'stock',
            'sort',
            'per_page',
            'page',
        ]));
    }
}
