<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Models\ContaPymeInventoryMapping;
use App\Modules\Inventory\ValueObjects\ContaPymePriceLookup;
use App\Modules\Inventory\ValueObjects\ContaPymePriceSyncReport;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final class ContaPymePriceSyncRunner
{
    /** @var list<array{sku:string,scope:string,message:string}> */
    private array $errors = [];

    public function __construct(private readonly ContaPymePriceService $prices) {}

    /** @param callable(string,string):void|null $emit */
    public function run(
        string $origin = 'command',
        ?string $sku = null,
        int $limit = 0,
        bool $dryRun = false,
        bool $force = false,
        ?callable $emit = null,
    ): ContaPymePriceSyncReport {
        $this->errors = [];
        $startedAt = now();
        $runId = (string) Str::uuid();
        $mode = filled($sku) ? 'sku' : 'full';
        $stats = [
            'processed' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'missing_contapyme' => 0,
            'anomalous' => 0,
            'failed' => 0,
            'dry_run' => $dryRun ? 1 : 0,
        ];

        try {
            $products = $this->products($sku, $limit);

            foreach ($products as $product) {
                $this->syncTarget($product, trim((string) $product->sku), $dryRun, $stats, $emit);

                foreach ($product->variants as $variant) {
                    $mapping = $variant->contapymeMapping;

                    if (! $mapping || trim((string) $mapping->irecurso) === '') {
                        continue;
                    }

                    $this->syncTarget($variant, trim((string) $mapping->irecurso), $dryRun, $stats, $emit);
                }
            }
        } catch (Throwable $e) {
            $stats['failed']++;
            $this->errors[] = ['sku' => (string) ($sku ?? ''), 'scope' => 'ejecucion', 'message' => $e->getMessage()];
            $this->emit($emit, 'CONTAPYME_PRICE_ERROR: la sincronización no pudo completarse.', 'error');
        }

        $finishedAt = now();

        Log::info('contapyme.price_sync_completed', [
            'run_id' => $runId,
            'mode' => $mode,
            'stats' => $stats,
            'dry_run' => $dryRun,
            'force' => $force,
        ]);

        return new ContaPymePriceSyncReport($runId, $origin, $mode, $startedAt, $finishedAt, $stats, $this->errors);
    }

    /** @return Collection<int,Product> */
    private function products(?string $sku, int $limit): Collection
    {
        $query = Product::query()
            ->active()
            ->whereNotNull('sku')
            ->where('sku', '<>', '')
            ->with(['variants' => fn ($q) => $q->active()->with('contapymeMapping')])
            ->orderBy('id');

        if (filled($sku)) {
            $query->where('sku', trim((string) $sku));
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /** @param Product|ProductVariant $target @param array<string,int> $stats */
    private function syncTarget($target, string $irecurso, bool $dryRun, array &$stats, ?callable $emit): void
    {
        $stats['processed']++;
        $scope = $target instanceof ProductVariant ? 'variant' : 'product';
        $gold = $this->prices->calculatedPrice($irecurso, (string) config('contapyme.prices.method', '1'), (string) config('contapyme.prices.lists.gold', '1'));
        $silver = $this->prices->calculatedPrice($irecurso, (string) config('contapyme.prices.method', '1'), (string) config('contapyme.prices.lists.silver', '3'));

        if ($gold->status !== 'ok' || $silver->status !== 'ok') {
            $status = $gold->status === 'missing_contapyme' || $silver->status === 'missing_contapyme'
                ? 'missing_contapyme' : 'error';
            $message = $gold->message ?? $silver->message ?? 'No fue posible consultar precios.';

            if (! $dryRun) {
                $target->forceFill([
                    'price_sync_status' => $status,
                    'price_sync_error' => $message,
                ])->save();
            }

            $stats[$status === 'missing_contapyme' ? 'missing_contapyme' : 'failed']++;
            $this->recordError($irecurso, $scope, $message);
            return;
        }

        $goldValue = (string) $gold->price;
        $silverValue = (string) $silver->price;

        if ($this->toCents($silverValue) < $this->toCents($goldValue)) {
            $message = "Precio Silver {$silverValue} inferior a Gold {$goldValue}.";

            if (! $dryRun) {
                $target->forceFill([
                    'price_sync_status' => 'anomalous',
                    'price_sync_error' => $message,
                    'price_sync_observed_gold' => $goldValue,
                    'price_sync_observed_silver' => $silverValue,
                ])->save();
            }

            $stats['anomalous']++;
            $this->recordError($irecurso, $scope, $message);
            Log::warning('contapyme.price_sync_anomalous', [
                'irecurso' => $irecurso,
                'gold' => $goldValue,
                'silver' => $silverValue,
            ]);
            return;
        }

        $changed = (string) $target->price !== $goldValue || (string) $target->silver_price !== $silverValue
            || $target->price_sync_status !== 'synced';

        if (! $dryRun && $changed) {
            $target->forceFill([
                'price' => $goldValue,
                'silver_price' => $silverValue,
                'price_synced_at' => now(),
                'price_sync_status' => 'synced',
                'price_sync_error' => null,
                'price_sync_observed_gold' => $goldValue,
                'price_sync_observed_silver' => $silverValue,
            ])->save();
        }

        $stats[$changed ? 'updated' : 'unchanged']++;
    }

    private function recordError(string $sku, string $scope, string $message): void
    {
        if (count($this->errors) < 20) {
            $this->errors[] = compact('sku', 'scope', 'message');
        }
    }

    private function toCents(string $value): int
    {
        [$whole, $fraction] = array_pad(explode('.', trim($value), 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    /** @param callable(string,string):void|null $emit */
    private function emit(?callable $emit, string $message, string $level): void
    {
        if ($emit) {
            $emit($message, $level);
        }
    }
}
