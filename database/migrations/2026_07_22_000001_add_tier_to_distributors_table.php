<?php

use App\Modules\Shared\Enums\DistributorTier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('distributors', function (Blueprint $table): void {
            $table->string('tier')->default(DistributorTier::Silver->value)->index()->after('status');
            $table->timestamp('tier_changed_at')->nullable()->after('tier');
            $table->foreignId('tier_changed_by_id')
                ->nullable()
                ->after('tier_changed_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('distributors', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('tier_changed_by_id');
            $table->dropColumn(['tier', 'tier_changed_at']);
        });
    }
};
