<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Actions\SyncProductsFromInvenTreeAction;
use App\Modules\Inventory\Actions\SyncStockFromInvenTreeAction;
use Illuminate\Support\Facades\Log;

class InvenTreeSyncService
{
    public function __construct(
        private readonly InvenTreeApiClient $apiClient,
        private readonly SyncProductsFromInvenTreeAction $syncProducts,
        private readonly SyncStockFromInvenTreeAction $syncStock,
    ) {}

    public function syncAll(string $type = 'all', ?callable $onProgress = null): array
    {
        $results = [
            'products' => $this->emptyProductStats(),
            'stock' => $this->emptyStockStats(),
            'started_at' => now()->toIso8601String(),
            'finished_at' => null,
            'duration_ms' => 0,
        ];

        $start = microtime(true);

        try {
            Log::info('InvenTree sync: iniciando sincronización completa');

            if ($type === 'all' || $type === 'products') {
                $results['products'] = $this->syncProducts->execute(function (int $current, int $total, array $part) use ($onProgress): void {
                    if ($onProgress !== null) {
                        $onProgress($current, $total, 'products', $part['name'] ?? '');
                    }
                });

                Log::info('InvenTree sync: productos sincronizados', $results['products']);
            }

            if ($type === 'all' || $type === 'stock') {
                $results['stock'] = $this->syncStock->execute(function (int $current, int $total, array $part) use ($onProgress): void {
                    if ($onProgress !== null) {
                        $onProgress($current, $total, 'stock', $part['name'] ?? '');
                    }
                });

                Log::info('InvenTree sync: stocks sincronizados', $results['stock']);
            }
        } catch (\Throwable $e) {
            Log::error('InvenTree sync: error fatal', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $results['error'] = $e->getMessage();
        }

        $results['finished_at'] = now()->toIso8601String();
        $results['duration_ms'] = (int) ((microtime(true) - $start) * 1000);

        return $results;
    }

    private function emptyProductStats(): array
    {
        return [
            'total' => 0,
            'updated_price' => 0,
            'updated_stock' => 0,
            'updated_both' => 0,
            'skipped' => 0,
            'not_found' => 0,
            'errors' => 0,
        ];
    }

    private function emptyStockStats(): array
    {
        return [
            'total' => 0,
            'matched' => 0,
            'updated' => 0,
            'skipped_variants' => 0,
            'unmatched' => 0,
            'errors' => 0,
        ];
    }

    public function testConnection(): array
    {
        try {
            $parts = $this->apiClient->getParts(['limit' => 1]);

            return [
                'success' => true,
                'message' => 'Conexión exitosa a InvenTree API',
                'parts_count' => $parts[0]['pk'] ?? 0,
                'server' => config('services.inventree.base_url'),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error de conexión: '.$e->getMessage(),
            ];
        }
    }
}
