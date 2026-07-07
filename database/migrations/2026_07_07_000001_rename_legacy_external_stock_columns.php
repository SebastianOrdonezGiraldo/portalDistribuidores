<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $legacyStockColumn = 'inven'.'tree_stock';

        foreach (['products', 'product_variants'] as $tableName) {
            if (Schema::hasColumn($tableName, $legacyStockColumn) && ! Schema::hasColumn($tableName, 'external_stock')) {
                Schema::table($tableName, function (Blueprint $table) use ($legacyStockColumn): void {
                    $table->renameColumn($legacyStockColumn, 'external_stock');
                });
            }
        }

        if (Schema::hasTable('stock_movements')) {
            DB::table('stock_movements')
                ->where('source', 'inven'.'tree_sync')
                ->update(['source' => 'external_sync']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('stock_movements')) {
            DB::table('stock_movements')
                ->where('source', 'external_sync')
                ->update(['source' => 'inven'.'tree_sync']);
        }
    }
};
