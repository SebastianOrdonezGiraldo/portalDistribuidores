<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['products', 'product_variants'] as $tableName) {
            if (! Schema::hasColumn($tableName, 'external_stock')) {
                continue;
            }

            DB::table($tableName)
                ->whereNotNull('external_stock')
                ->orderBy('id')
                ->select(['id', 'stock', 'external_stock', 'reserved_stock'])
                ->chunkById(500, function ($rows) use ($tableName): void {
                    foreach ($rows as $row) {
                        $stock = $row->stock;

                        if ($stock === null) {
                            $stock = max(0, (float) $row->external_stock - (float) ($row->reserved_stock ?? 0));
                        }

                        DB::table($tableName)
                            ->where('id', $row->id)
                            ->update([
                                'stock' => $stock,
                                'external_stock' => null,
                                'reserved_stock' => 0,
                            ]);
                    }
                });
        }
    }

    public function down(): void
    {
        // La conversion a stock manual es intencionalmente irreversible:
        // no hay una fuente externa activa que reconstruya external_stock.
    }
};
