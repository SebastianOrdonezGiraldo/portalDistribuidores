<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Shared\Contracts\InventorySyncInterface;
use App\Modules\Shared\ValueObjects\InventoryItemData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ContaPyme adapter for stock-only synchronization.
 *
 * The portal owns prices and catalog data. ContaPyme only feeds the local stock
 * field so cart and quotation validation can stay fast and isolated from the
 * external API. HTTP and authentication live in ContaPymeClient.
 */
class ContaPymeInventoryService implements InventorySyncInterface
{
    private ?ContaPymeClient $client = null;

    private ?string $lastError = null;

    public function __construct(?ContaPymeClient $client = null)
    {
        $this->client = $client;
    }

    /**
     * Verify credentials and endpoint reachability without mutating inventory.
     */
    public function testConnection(): bool
    {
        $this->lastError = null;

        try {
            $authenticated = $this->authenticate() !== '';

            if (! $authenticated) {
                $this->lastError ??= $this->diagnosticError(null, 'ContaPyme no devolvio keyagente.');
            }

            return $authenticated;
        } catch (\Throwable $e) {
            $this->lastError = $this->diagnosticError($e->getMessage());

            Log::error('contapyme.test_connection_failed', [
                'error' => $this->lastError,
                'base_url_configured' => $this->client()->hasBaseUrl(),
            ]);

            return false;
        }
    }

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Run a non-mutating authentication and Agent health check.
     *
     * @return array{authenticated:bool, agent_status:string|null, auth_metadata:array<string,string>, error:string|null}
     */
    public function diagnose(): array
    {
        $this->lastError = null;

        try {
            $authenticated = $this->authenticate() !== '';
        } catch (\Throwable $e) {
            $this->lastError = $this->diagnosticError($e->getMessage());

            Log::error('contapyme.diagnose_failed', [
                'error' => $this->lastError,
                'auth_metadata' => $this->client()->authMetadata(),
            ]);

            return [
                'authenticated' => false,
                'agent_status' => null,
                'auth_metadata' => $this->client()->authMetadata(),
                'error' => $this->lastError,
            ];
        }

        if (! $authenticated) {
            return [
                'authenticated' => false,
                'agent_status' => null,
                'auth_metadata' => $this->client()->authMetadata(),
                'error' => $this->lastError ?? $this->diagnosticError(null, 'ContaPyme no devolvio keyagente.'),
            ];
        }

        try {
            $testResponse = $this->call(
                serverClass: 'TBasicoGeneral',
                function: 'Test',
                dataJson: [],
            );
            $agentStatus = $this->agentStatusFromResponse($testResponse);

            if ($agentStatus === null) {
                $this->lastError = $this->diagnosticError(null, 'ContaPyme no devolvio un estado valido del Agente.');
            }

            Log::info('contapyme.diagnose_completed', [
                'agent_status' => $agentStatus,
                'auth_metadata' => $this->client()->authMetadata(),
            ]);

            return [
                'authenticated' => true,
                'agent_status' => $agentStatus,
                'auth_metadata' => $this->client()->authMetadata(),
                'error' => $this->lastError,
            ];
        } catch (\Throwable $e) {
            $this->lastError = $this->diagnosticError($e->getMessage());

            Log::error('contapyme.diagnose_agent_failed', [
                'error' => $this->lastError,
                'auth_metadata' => $this->client()->authMetadata(),
            ]);

            return [
                'authenticated' => true,
                'agent_status' => null,
                'auth_metadata' => $this->client()->authMetadata(),
                'error' => $this->lastError,
            ];
        }
    }

    public function diagnosticError(?string $message, string $fallback = 'ContaPyme no devolvio una causa especifica.'): string
    {
        return $this->client()->diagnosticError($message, $fallback);
    }

    /**
     * Verify that an inventory item exists independently from its physical balance.
     */
    public function productExists(string $sku): ?bool
    {
        $this->lastError = null;
        $sku = trim($sku);

        if ($sku === '') {
            $this->lastError = 'No se puede validar un SKU vacio en ContaPyme.';

            return null;
        }

        try {
            $data = $this->call(
                serverClass: 'TCatElemInv',
                function: 'GetExisteElemInv',
                dataJson: ['irecurso' => $sku],
            );
        } catch (\Throwable $e) {
            $this->lastError = $this->diagnosticError($e->getMessage());

            Log::error('contapyme.product_exists_failed', [
                'sku' => $sku,
                'error' => $this->lastError,
            ]);

            return null;
        }

        if ($data === null && $this->lastError !== null) {
            return null;
        }

        $exists = $this->normalizeBoolean(data_get($data, 'existe'));

        if ($exists !== null) {
            return $exists;
        }

        $this->lastError = $this->diagnosticError(
            null,
            'ContaPyme no devolvio una confirmacion de existencia valida.',
        );

        Log::error('contapyme.product_exists_invalid_response', [
            'sku' => $sku,
            'data_type' => get_debug_type($data),
        ]);

        return null;
    }

    /**
     * Fetch ContaPyme projected stock (disponible) for one SKU.
     *
     * Empty balance lists mean zero. Use productExists() when the caller also
     * needs to confirm the SKU exists independently.
     */
    public function getProductInfo(string $sku): ?InventoryItemData
    {
        $this->lastError = null;
        $sku = trim($sku);

        if ($sku === '') {
            return null;
        }

        try {
            $data = $this->call(
                serverClass: 'TCatElemInv',
                function: 'GetSaldosProductosEnBodegas',
                dataJson: [
                    'irecurso' => $sku,
                    'binventarioproyectado' => 'T',
                    'bnombreinventario' => 'T',
                ],
            );

            if ($data === null && $this->lastError !== null) {
                return null;
            }

            $products = data_get($data, 'listaproductos', []);
            $row = is_array($products) ? collect($products)->first(
                fn (mixed $item): bool => is_array($item)
                    && trim((string) ($item['irecurso'] ?? '')) === $sku,
            ) : null;

            $stock = $this->stockFromBulkWarehouseRows(
                is_array($row) ? (array) ($row['listabodegas'] ?? []) : [],
            );

            return new InventoryItemData(
                sku: $sku,
                stock: $stock,
                externalId: $sku,
                rawData: is_array($data) ? $data : ['datos' => $data],
            );
        } catch (\Throwable $e) {
            $this->lastError = $this->diagnosticError($e->getMessage());

            Log::error('contapyme.get_product_stock_failed', [
                'sku' => $sku,
                'error' => $this->lastError,
            ]);

            return null;
        }
    }

    /**
     * Fetch ContaPyme projected stock for all products with balance.
     *
     * @return Collection<int, InventoryItemData>
     */
    public function listProducts(): Collection
    {
        $this->lastError = null;

        try {
            $data = $this->call(
                serverClass: 'TCatElemInv',
                function: 'GetSaldosProductosEnBodegas',
                dataJson: [
                    'iinventario' => 'T',
                    'bunidadrecurso' => 'T',
                    'binventarioproyectado' => 'T',
                    'bnombreinventario' => 'T',
                ],
            );
        } catch (\Throwable $e) {
            $this->lastError = $this->diagnosticError($e->getMessage());

            Log::error('contapyme.list_products_failed', [
                'error' => $this->lastError,
            ]);

            return collect();
        }

        if (! is_array($data)) {
            $this->lastError ??= $this->diagnosticError(
                null,
                'ContaPyme no devolvio datos de inventario validos.',
            );

            Log::error('contapyme.list_products_invalid_response', [
                'error' => $this->lastError,
            ]);

            return collect();
        }

        $products = data_get($data, 'listaproductos', []);

        if (! is_array($products)) {
            $this->lastError = $this->diagnosticError(
                null,
                'ContaPyme no devolvio listaproductos.',
            );

            Log::error('contapyme.list_products_missing_list', [
                'error' => $this->lastError,
            ]);

            return collect();
        }

        return collect($products)
            ->map(function (mixed $row): ?InventoryItemData {
                if (! is_array($row)) {
                    return null;
                }

                $sku = trim((string) ($row['irecurso'] ?? ''));

                if ($sku === '') {
                    return null;
                }

                return new InventoryItemData(
                    sku: $sku,
                    stock: $this->stockFromBulkWarehouseRows((array) ($row['listabodegas'] ?? [])),
                    externalId: $sku,
                    rawData: $row,
                );
            })
            ->filter()
            ->values();
    }

    /**
     * Pull stock for one SKU and persist it to the matching portal product.
     *
     * @return bool true only when stock changed
     */
    public function syncProductStock(string $sku): bool
    {
        $product = Product::query()->where('sku', trim($sku))->first();

        if ($product === null) {
            Log::warning('contapyme.sync_product_stock_missing_local_product', [
                'sku' => $sku,
            ]);

            return false;
        }

        return $this->syncProduct($product)['changed'];
    }

    /**
     * @return array{status:string, changed:bool, stock:float|null}
     */
    public function syncProduct(Product $product): array
    {
        $exists = $this->productExists((string) $product->sku);

        if ($exists === null) {
            return $this->failedSyncResult($product);
        }

        if (! $exists) {
            return $this->markProductMissingInContaPyme($product);
        }

        $info = $this->getProductInfo((string) $product->sku);

        if ($info === null || $info->stock === null) {
            return $this->failedSyncResult($product);
        }

        return $this->syncProductFromPhysicalStock(
            product: $product,
            physicalStock: (float) $info->stock,
            reservedStock: 0.0,
        );
    }

    /**
     * Preserve local availability for a SKU that ContaPyme explicitly does not know.
     *
     * @return array{status:string, changed:bool, stock:float|null}
     */
    public function markProductMissingInContaPyme(Product $product): array
    {
        $stock = is_numeric($product->stock) ? (float) $product->stock : null;

        DB::transaction(function () use ($product): void {
            $lockedProduct = Product::query()
                ->whereKey($product->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedProduct->stock_sync_status === 'missing_contapyme' && $lockedProduct->stock_synced_at === null) {
                return;
            }

            $lockedProduct->forceFill([
                'stock_sync_status' => 'missing_contapyme',
                'stock_synced_at' => null,
            ])->save();
        });

        return [
            'status' => 'missing_contapyme',
            'changed' => false,
            'stock' => $stock,
        ];
    }

    /**
     * Persist the unmodified ContaPyme balance as local base stock.
     *
     * Local reservations live in reserved_stock/inventory_holds and are never
     * folded into this value. The reservedStock argument remains only for source
     * compatibility with older callers and is intentionally ignored.
     *
     * @return array{status:string, changed:bool, stock:float}
     */
    public function syncProductFromPhysicalStock(
        Product $product,
        float $physicalStock,
        float $reservedStock = 0.0,
        string $syncStatus = 'synced',
    ): array {
        $baseStock = round(max(0, $physicalStock), 2);
        $previousStock = null;
        $newStock = $baseStock;
        $changed = false;

        DB::transaction(function () use (
            $product,
            $baseStock,
            $syncStatus,
            &$previousStock,
            &$newStock,
            &$changed,
        ): void {
            $lockedProduct = Product::query()
                ->whereKey($product->id)
                ->lockForUpdate()
                ->firstOrFail();

            $previousStock = is_numeric($lockedProduct->stock) ? (float) $lockedProduct->stock : null;

            $newStock = $baseStock;
            $changed = $previousStock === null || abs($previousStock - $newStock) > 0.00001;

            $lockedProduct->forceFill([
                'stock' => $newStock,
                'stock_synced_at' => now(),
                'stock_sync_status' => $syncStatus,
            ])->save();

            if ($changed) {
                StockMovement::record(
                    product: $lockedProduct,
                    previousStock: $previousStock,
                    newStock: $newStock,
                    source: 'contapyme_sync',
                );
            }
        });

        return [
            'status' => $changed ? 'updated' : 'unchanged',
            'changed' => $changed,
            'stock' => $newStock,
        ];
    }

    /**
     * Resolved lazily so test doubles that skip the parent constructor still work.
     */
    private function client(): ContaPymeClient
    {
        return $this->client ??= app(ContaPymeClient::class);
    }

    /**
     * Force a fresh keyagente, keeping the client's failure reason as ours.
     */
    private function authenticate(): string
    {
        try {
            return $this->client()->authenticate(forceRefresh: true);
        } finally {
            $this->lastError = $this->client()->lastError();
        }
    }

    /**
     * @phpstan-impure
     */
    private function call(string $serverClass, string $function, array $dataJson): mixed
    {
        try {
            return $this->client()->call($serverClass, $function, $dataJson);
        } finally {
            $this->lastError = $this->client()->lastError();
        }
    }

    /**
     * Sum ContaPyme warehouse balances. Prefers projected qty (qinvproyectado).
     *
     * @param  array<int, mixed>  $rows
     */
    private function stockFromBulkWarehouseRows(array $rows): float
    {
        return (float) collect($rows)
            ->filter(fn (mixed $row): bool => is_array($row))
            ->map(function (array $row): ?float {
                foreach (['qinvproyectado', 'qinvcontable', 'qinvfisico', 'qproducto'] as $field) {
                    $value = $this->normalizeNumeric($row[$field] ?? null);

                    if ($value !== null) {
                        return $value;
                    }
                }

                return null;
            })
            ->filter(fn (?float $value): bool => $value !== null)
            ->sum();
    }

    private function normalizeNumeric(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        return null;
    }

    private function normalizeBoolean(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (! is_string($value) && ! is_int($value)) {
            return null;
        }

        return match (strtolower(trim((string) $value))) {
            'true', '1' => true,
            'false', '0' => false,
            default => null,
        };
    }

    private function agentStatusFromResponse(mixed $response): ?string
    {
        if (is_string($response) && trim($response) !== '') {
            return $this->client()->redact(trim($response));
        }

        if (! is_array($response)) {
            return null;
        }

        foreach (['estado', 'status', 'mensaje', 'resultado'] as $key) {
            $value = data_get($response, $key);

            if (is_scalar($value) && trim((string) $value) !== '') {
                return $this->client()->redact((string) $value);
            }
        }

        return null;
    }

    /**
     * @return array{status:string, changed:bool, stock:float|null}
     */
    private function failedSyncResult(Product $product): array
    {
        return [
            'status' => 'failed',
            'changed' => false,
            'stock' => is_numeric($product->stock) ? (float) $product->stock : null,
        ];
    }
}
