<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('commerce_pricing_rule_id')
                ->nullable()
                ->after('distributor_tier_snapshot')
                ->constrained('commerce_pricing_rules')
                ->nullOnDelete();

            // Pedido mínimo por nivel
            $table->string('minimum_order_tier_snapshot', 16)->nullable()->after('commerce_pricing_rule_id');
            $table->boolean('minimum_order_enabled_snapshot')->nullable()->after('minimum_order_tier_snapshot');
            $table->unsignedInteger('minimum_order_amount_snapshot')->nullable()->after('minimum_order_enabled_snapshot');
            $table->decimal('minimum_order_evaluated_amount', 14, 2)->nullable()->after('minimum_order_amount_snapshot');
            $table->boolean('minimum_order_reached')->nullable()->after('minimum_order_evaluated_amount');
            $table->string('minimum_order_decision_reason_snapshot', 64)->nullable()->after('minimum_order_reached');

            // Umbral de precios Oro
            $table->boolean('gold_pricing_threshold_enabled_snapshot')->nullable()->after('minimum_order_decision_reason_snapshot');
            $table->unsignedInteger('gold_pricing_threshold_amount_snapshot')->nullable()->after('gold_pricing_threshold_enabled_snapshot');
            $table->string('gold_pricing_threshold_basis_snapshot', 32)->nullable()->after('gold_pricing_threshold_amount_snapshot');
            $table->string('gold_pricing_decision_reason_snapshot', 64)->nullable()->after('gold_pricing_threshold_basis_snapshot');
            $table->boolean('gold_pricing_applied')->nullable()->after('gold_pricing_decision_reason_snapshot');
            $table->decimal('silver_candidate_total', 14, 2)->nullable()->after('gold_pricing_applied');
            $table->decimal('gold_candidate_total', 14, 2)->nullable()->after('silver_candidate_total');
            $table->decimal('gold_savings_total', 14, 2)->nullable()->after('gold_candidate_total');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('commerce_pricing_rule_id');
            $table->dropColumn([
                'minimum_order_tier_snapshot',
                'minimum_order_enabled_snapshot',
                'minimum_order_amount_snapshot',
                'minimum_order_evaluated_amount',
                'minimum_order_reached',
                'minimum_order_decision_reason_snapshot',
                'gold_pricing_threshold_enabled_snapshot',
                'gold_pricing_threshold_amount_snapshot',
                'gold_pricing_threshold_basis_snapshot',
                'gold_pricing_decision_reason_snapshot',
                'gold_pricing_applied',
                'silver_candidate_total',
                'gold_candidate_total',
                'gold_savings_total',
            ]);
        });
    }
};
