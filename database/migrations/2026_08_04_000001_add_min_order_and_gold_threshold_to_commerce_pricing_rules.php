<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commerce_pricing_rules', function (Blueprint $table) {
            $table->boolean('silver_min_order_enabled')->default(false)->after('silver_rounding_multiple');
            $table->unsignedInteger('silver_min_order_amount')->default(1_000_000)->after('silver_min_order_enabled');
            $table->boolean('gold_min_order_enabled')->default(false)->after('silver_min_order_amount');
            $table->unsignedInteger('gold_min_order_amount')->default(1_000_000)->after('gold_min_order_enabled');
            $table->boolean('gold_pricing_threshold_enabled')->default(false)->after('gold_min_order_amount');
            $table->unsignedInteger('gold_pricing_threshold_amount')->default(1_000_000)->after('gold_pricing_threshold_enabled');
            $table->string('gold_pricing_threshold_basis', 32)->default('gold_candidate')->after('gold_pricing_threshold_amount');
        });
    }

    public function down(): void
    {
        Schema::table('commerce_pricing_rules', function (Blueprint $table) {
            $table->dropColumn([
                'silver_min_order_enabled',
                'silver_min_order_amount',
                'gold_min_order_enabled',
                'gold_min_order_amount',
                'gold_pricing_threshold_enabled',
                'gold_pricing_threshold_amount',
                'gold_pricing_threshold_basis',
            ]);
        });
    }
};
