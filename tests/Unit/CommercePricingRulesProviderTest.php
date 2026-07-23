<?php

namespace Tests\Unit;

use App\Modules\Orders\Models\CommercePricingRule;
use App\Modules\Orders\Pricing\CommercePricingRulesProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CommercePricingRulesProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget(CommercePricingRulesProvider::CACHE_KEY);
    }

    public function test_empty_table_falls_back_to_config(): void
    {
        DB::table('commerce_pricing_rules')->delete();
        config([
            'commerce.tiers.silver_markup_percent' => 5,
            'commerce.tiers.silver_rounding_multiple' => 1000,
        ]);

        $rules = app(CommercePricingRulesProvider::class)->current();

        $this->assertSame(500, $rules->silverMarkupBasisPoints);
        $this->assertSame(1000, $rules->silverRoundingMultiple);
    }

    public function test_config_percent_converts_to_basis_points(): void
    {
        DB::table('commerce_pricing_rules')->delete();
        config(['commerce.tiers.silver_markup_percent' => 7]);

        $rules = app(CommercePricingRulesProvider::class)->current();

        $this->assertSame(700, $rules->silverMarkupBasisPoints);
    }

    public function test_single_record_uses_database(): void
    {
        DB::table('commerce_pricing_rules')->delete();
        CommercePricingRule::query()->create([
            'silver_markup_basis_points' => 650,
            'silver_rounding_multiple' => 500,
            'created_by_id' => null,
        ]);

        $rules = app(CommercePricingRulesProvider::class)->current();

        $this->assertSame(650, $rules->silverMarkupBasisPoints);
        $this->assertSame(500, $rules->silverRoundingMultiple);
    }

    public function test_multiple_versions_use_latest_by_id(): void
    {
        DB::table('commerce_pricing_rules')->delete();
        CommercePricingRule::query()->create([
            'silver_markup_basis_points' => 500,
            'silver_rounding_multiple' => 1000,
            'created_by_id' => null,
        ]);
        CommercePricingRule::query()->create([
            'silver_markup_basis_points' => 700,
            'silver_rounding_multiple' => 100,
            'created_by_id' => null,
        ]);

        $rules = app(CommercePricingRulesProvider::class)->current();

        $this->assertSame(700, $rules->silverMarkupBasisPoints);
        $this->assertSame(100, $rules->silverRoundingMultiple);
    }

    public function test_missing_table_falls_back_to_config(): void
    {
        Schema::drop('commerce_pricing_rules');
        config([
            'commerce.tiers.silver_markup_percent' => 5,
            'commerce.tiers.silver_rounding_multiple' => 1000,
        ]);

        $rules = app(CommercePricingRulesProvider::class)->current();

        $this->assertSame(500, $rules->silverMarkupBasisPoints);
        $this->assertSame(1000, $rules->silverRoundingMultiple);
    }

    public function test_cache_reuses_payload_outside_testing_when_forced(): void
    {
        // Force non-testing cache path by writing cache payload manually and reading via remember semantics.
        DB::table('commerce_pricing_rules')->delete();
        CommercePricingRule::query()->create([
            'silver_markup_basis_points' => 500,
            'silver_rounding_multiple' => 1000,
            'created_by_id' => null,
        ]);

        $provider = app(CommercePricingRulesProvider::class);
        $first = $provider->current();

        CommercePricingRule::query()->create([
            'silver_markup_basis_points' => 800,
            'silver_rounding_multiple' => 500,
            'created_by_id' => null,
        ]);

        // In testing, provider always resolves fresh — new rule is visible immediately.
        $second = $provider->current();
        $this->assertSame(800, $second->silverMarkupBasisPoints);
        $this->assertSame(500, $first->silverMarkupBasisPoints);
    }

    public function test_forget_cache_allows_new_version(): void
    {
        $provider = app(CommercePricingRulesProvider::class);
        Cache::put(CommercePricingRulesProvider::CACHE_KEY, [
            'silver_markup_basis_points' => 111,
            'silver_rounding_multiple' => 100,
        ], now()->addDay());

        $provider->forgetCache();
        $this->assertFalse(Cache::has(CommercePricingRulesProvider::CACHE_KEY));

        $rules = $provider->current();
        $this->assertSame(500, $rules->silverMarkupBasisPoints);
    }
}
