<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['products', 'product_variants'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->decimal('silver_price', 14, 2)->nullable()->after('price');
                $table->timestamp('price_synced_at')->nullable()->after('silver_price');
                $table->string('price_sync_status', 32)->default('never_synced')->after('price_synced_at')->index();
                $table->text('price_sync_error')->nullable()->after('price_sync_status');
                $table->decimal('price_sync_observed_gold', 14, 2)->nullable()->after('price_sync_error');
                $table->decimal('price_sync_observed_silver', 14, 2)->nullable()->after('price_sync_observed_gold');
            });
        }
    }

    public function down(): void
    {
        foreach (['products', 'product_variants'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn([
                    'silver_price',
                    'price_synced_at',
                    'price_sync_status',
                    'price_sync_error',
                    'price_sync_observed_gold',
                    'price_sync_observed_silver',
                ]);
            });
        }
    }
};
