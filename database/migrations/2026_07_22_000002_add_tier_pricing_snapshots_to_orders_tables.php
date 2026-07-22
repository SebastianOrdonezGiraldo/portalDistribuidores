<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('distributor_tier_snapshot')->nullable()->after('status');
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->decimal('base_unit_price', 14, 2)->nullable()->after('price_each');
            $table->decimal('silver_unit_price', 14, 2)->nullable()->after('base_unit_price');
            $table->decimal('unit_savings', 14, 2)->nullable()->after('silver_unit_price');
            $table->decimal('line_savings', 14, 2)->nullable()->after('subtotal');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropColumn(['base_unit_price', 'silver_unit_price', 'unit_savings', 'line_savings']);
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('distributor_tier_snapshot');
        });
    }
};
