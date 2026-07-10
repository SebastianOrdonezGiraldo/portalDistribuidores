<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            if (! Schema::hasColumn('products', 'stock_synced_at')) {
                $table->timestamp('stock_synced_at')->nullable()->after('stock');
            }

            if (! Schema::hasColumn('products', 'stock_sync_status')) {
                $table->string('stock_sync_status', 32)->nullable()->after('stock_synced_at')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            if (Schema::hasColumn('products', 'stock_sync_status')) {
                $table->dropColumn('stock_sync_status');
            }

            if (Schema::hasColumn('products', 'stock_synced_at')) {
                $table->dropColumn('stock_synced_at');
            }
        });
    }
};
