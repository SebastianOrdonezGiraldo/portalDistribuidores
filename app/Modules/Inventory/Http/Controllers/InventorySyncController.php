<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Services\InvenTreeSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventorySyncController extends Controller
{
    public function index(InvenTreeSyncService $syncService): View
    {
        $connectionStatus = $syncService->testConnection();

        return view('admin.inventory.index', [
            'connectionStatus' => $connectionStatus,
        ]);
    }

    public function sync(Request $request, InvenTreeSyncService $syncService): RedirectResponse
    {
        $type = (string) $request->input('type', 'all');

        if (! in_array($type, ['all', 'products', 'stock'], true)) {
            $type = 'all';
        }

        $results = $syncService->syncAll();

        if (isset($results['error'])) {
            return redirect()
                ->route('admin.inventory.index')
                ->with('error', 'Error de sincronización: '.$results['error']);
        }

        return redirect()
            ->route('admin.inventory.index')
            ->with('success', 'Sincronización completada en '.($results['duration_ms'] ?? 0).' ms.')
            ->with('syncResults', $results);
    }

    public function test(InvenTreeSyncService $syncService): RedirectResponse
    {
        $result = $syncService->testConnection();

        if ($result['success']) {
            return redirect()
                ->route('admin.inventory.index')
                ->with('success', $result['message']);
        }

        return redirect()
            ->route('admin.inventory.index')
            ->with('error', $result['message']);
    }
}
