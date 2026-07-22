<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\AuthAccess\Services\DistributorTierService;
use App\Modules\Shared\Enums\DistributorTier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DistributorTierTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_distributor_defaults_to_silver(): void
    {
        $distributor = Distributor::factory()->create();

        $this->assertSame(DistributorTier::Silver, $distributor->tier);
        $this->assertTrue($distributor->isSilver());
        $this->assertFalse($distributor->isGold());
    }

    public function test_distributor_created_without_tier_column_defaults_to_silver_in_database(): void
    {
        // Insert bypassing the model default to prove the DB default is Plata,
        // mirroring existing rows migrated before this feature.
        $id = DB::table('distributors')->insertGetId([
            'name' => 'Existente Migrado',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $distributor = Distributor::findOrFail($id);

        $this->assertSame(DistributorTier::Silver, $distributor->tier);
    }

    public function test_factory_gold_state_sets_gold_tier(): void
    {
        $distributor = Distributor::factory()->gold()->create();

        $this->assertSame(DistributorTier::Gold, $distributor->tier);
        $this->assertTrue($distributor->isGold());
        $this->assertFalse($distributor->isSilver());
    }

    public function test_tier_is_cast_to_enum(): void
    {
        $distributor = Distributor::factory()->gold()->create();

        $this->assertInstanceOf(DistributorTier::class, $distributor->fresh()->tier);
    }

    public function test_service_changes_tier_and_records_audit_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $distributor = Distributor::factory()->silver()->create();

        $updated = app(DistributorTierService::class)->changeTier($distributor, DistributorTier::Gold, $admin);

        $this->assertSame(DistributorTier::Gold, $updated->tier);
        $this->assertNotNull($updated->tier_changed_at);
        $this->assertSame($admin->id, $updated->tier_changed_by_id);
        $this->assertTrue($updated->tierChangedBy->is($admin));
    }

    public function test_service_is_noop_when_tier_is_unchanged(): void
    {
        $admin = User::factory()->admin()->create();
        $distributor = Distributor::factory()->silver()->create();

        $updated = app(DistributorTierService::class)->changeTier($distributor, DistributorTier::Silver, $admin);

        $this->assertSame(DistributorTier::Silver, $updated->tier);
        $this->assertNull($updated->tier_changed_at);
        $this->assertNull($updated->tier_changed_by_id);
    }
}
