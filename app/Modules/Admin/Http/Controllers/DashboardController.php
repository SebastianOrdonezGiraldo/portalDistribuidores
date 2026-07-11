<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Services\DashboardDataService;
use App\Modules\Inventory\Services\ContaPymeSyncDispatcher;
use App\Modules\Inventory\Services\ContaPymeSyncState;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
    public function syncStock(
        Request $request,
        ContaPymeSyncState $syncState,
        ContaPymeSyncDispatcher $dispatcher,
    ): RedirectResponse {
        $availability = $syncState->availability();

        if ($availability['reason'] === 'disabled') {
            $message = 'La sincronización ContaPyme está deshabilitada en este entorno.';
            $syncState->block($message);

            return $this->redirectToProducts($request)->with('error', $message);
        }

        if (! $availability['can_run']) {
            return $this->redirectToProducts($request)
                ->with('error', $this->syncUnavailableMessage($availability));
        }

        if (! $dispatcher->dispatchIfAvailable('manual')) {
            $availability = $syncState->availability();

            return $this->redirectToProducts($request)
                ->with('error', $this->syncUnavailableMessage($availability));
        }

        return $this->redirectToProducts($request)
            ->with('success', 'Sincronización ContaPyme encolada. El resultado aparecerá al recargar el catálogo.');
    }

    /**
     * @param  array{can_run:bool, reason:string, available_at:string|null, retry_after:int}  $availability
     */
    private function syncUnavailableMessage(array $availability): string
    {
        if ($availability['reason'] === 'running') {
            return 'Ya hay una sincronización en curso. Espera a que termine.';
        }

        if ($availability['reason'] === 'cooldown') {
            return 'La sincronización está temporalmente bloqueada. Podrás volver a usarla en '
                .$this->formatRetryAfter($availability['retry_after']).'.';
        }

        return 'La sincronización ContaPyme no está disponible en este momento.';
    }

    private function formatRetryAfter(int $seconds): string
    {
        $seconds = max(1, $seconds);
        $minutes = intdiv($seconds, 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes === 0) {
            return $seconds.' segundos';
        }

        if ($remainingSeconds === 0) {
            return $minutes.' minutos';
        }

        return $minutes.' min '.$remainingSeconds.' s';
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
