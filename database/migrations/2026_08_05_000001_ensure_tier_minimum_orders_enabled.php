<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ensures the current commercial rule enforces tier minimum orders and keeps
 * the legacy gold pricing threshold disabled (Gold always pays Gold prices).
 *
 * Skipped in testing: Feature/Unit tests publish their own commercial snapshots.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        if (! Schema::hasTable('commerce_pricing_rules')) {
            return;
        }

        $latest = DB::table('commerce_pricing_rules')->orderByDesc('id')->first();

        $desired = [
            'silver_min_order_enabled' => true,
            'silver_min_order_amount' => 800_000,
            'gold_min_order_enabled' => true,
            'gold_min_order_amount' => 1_000_000,
            'gold_pricing_threshold_enabled' => false,
        ];

        if (
            $latest
            && (bool) $latest->silver_min_order_enabled === $desired['silver_min_order_enabled']
            && (int) $latest->silver_min_order_amount === $desired['silver_min_order_amount']
            && (bool) $latest->gold_min_order_enabled === $desired['gold_min_order_enabled']
            && (int) $latest->gold_min_order_amount === $desired['gold_min_order_amount']
            && (bool) $latest->gold_pricing_threshold_enabled === $desired['gold_pricing_threshold_enabled']
        ) {
            return;
        }

        DB::table('commerce_pricing_rules')->insert([
            'silver_markup_basis_points' => $latest->silver_markup_basis_points ?? 500,
            'silver_rounding_multiple' => $latest->silver_rounding_multiple ?? 1000,
            'silver_min_order_enabled' => $desired['silver_min_order_enabled'],
            'silver_min_order_amount' => $desired['silver_min_order_amount'],
            'gold_min_order_enabled' => $desired['gold_min_order_enabled'],
            'gold_min_order_amount' => $desired['gold_min_order_amount'],
            'gold_pricing_threshold_enabled' => $desired['gold_pricing_threshold_enabled'],
            'gold_pricing_threshold_amount' => $latest->gold_pricing_threshold_amount ?? 1_000_000,
            'gold_pricing_threshold_basis' => $latest->gold_pricing_threshold_basis ?? 'gold_candidate',
            'created_by_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Cache::forget('commerce.pricing-rules.current.v2');
        Cache::forget('commerce.pricing-rules.current.v1');
    }

    public function down(): void
    {
        // Append-only commercial rules are not rolled back.
    }
};
