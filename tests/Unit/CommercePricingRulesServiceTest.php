<?php

namespace Tests\Unit;

use App\Models\User;
use App\Modules\Orders\Models\CommercePricingRule;
use App\Modules\Orders\Pricing\CommercePricingRules;
use App\Modules\Orders\Pricing\CommercePricingRulesProvider;
use App\Modules\Orders\Pricing\CommercePricingRulesService;
use App\Modules\Orders\Pricing\DistributorPriceCalculator;
use App\Modules\Shared\Enums\DistributorTier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

class CommercePricingRulesServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget(CommercePricingRulesProvider::CACHE_KEY);
    }

    public function test_publish_creates_new_version_and_records_actor(): void
    {
        $admin = User::factory()->admin()->create();
        DB::table('commerce_pricing_rules')->delete();

        $result = app(CommercePricingRulesService::class)->publish(650, 500, $admin);

        $this->assertTrue($result->changed);
        $this->assertSame(650, $result->rule->silver_markup_basis_points);
        $this->assertSame(500, $result->rule->silver_rounding_multiple);
        $this->assertSame($admin->id, $result->rule->created_by_id);
        $this->assertDatabaseCount('commerce_pricing_rules', 1);
    }

    public function test_publish_does_not_duplicate_identical_values(): void
    {
        $admin = User::factory()->admin()->create();
        $before = CommercePricingRule::query()->count();

        $service = app(CommercePricingRulesService::class);
        $first = $service->publish(500, 1000, $admin);
        $second = $service->publish(500, 1000, $admin);

        $this->assertFalse($first->changed);
        $this->assertFalse($second->changed);
        $this->assertSame($first->rule->id, $second->rule->id);
        $this->assertSame($before, CommercePricingRule::query()->count());
    }

    public function test_publish_preserves_previous_versions(): void
    {
        $admin = User::factory()->admin()->create();
        $service = app(CommercePricingRulesService::class);

        $service->publish(500, 1000, $admin);
        $service->publish(700, 1000, $admin);

        $this->assertGreaterThanOrEqual(2, CommercePricingRule::query()->count());
        $latest = CommercePricingRule::query()->orderByDesc('id')->firstOrFail();
        $this->assertSame(700, $latest->silver_markup_basis_points);
    }

    public function test_publish_invalidates_cache_after_change(): void
    {
        $admin = User::factory()->admin()->create();
        Cache::put(CommercePricingRulesProvider::CACHE_KEY, [
            'silver_markup_basis_points' => 500,
            'silver_rounding_multiple' => 1000,
        ], now()->addDay());

        app(CommercePricingRulesService::class)->publish(700, 1000, $admin);

        $this->assertFalse(Cache::has(CommercePricingRulesProvider::CACHE_KEY));
    }

    public function test_publish_rejects_invalid_basis_points(): void
    {
        $admin = User::factory()->admin()->create();

        $this->expectException(InvalidArgumentException::class);
        app(CommercePricingRulesService::class)->publish(10001, 1000, $admin);
    }

    public function test_publish_rejects_invalid_rounding(): void
    {
        $admin = User::factory()->admin()->create();

        $this->expectException(InvalidArgumentException::class);
        app(CommercePricingRulesService::class)->publish(500, 0, $admin);
    }

    public function test_calculator_re_resolves_new_percentage_after_publish(): void
    {
        $admin = User::factory()->admin()->create();

        $before = app(DistributorPriceCalculator::class)
            ->calculate(10_000_000, DistributorTier::Silver);
        $this->assertSame('105000.00', $before->silverPriceDecimal());

        app(CommercePricingRulesService::class)->publish(700, 1000, $admin);

        // Fresh resolve from container (bind, not singleton).
        $after = app()->make(DistributorPriceCalculator::class)
            ->calculate(10_000_000, DistributorTier::Silver);

        $this->assertSame('107000.00', $after->silverPriceDecimal());
        $this->assertSame(700, app(CommercePricingRules::class)->silverMarkupBasisPoints);
    }
}
