<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table): void {
            $table->timestamp('stock_synced_at')->nullable()->after('stock');
            $table->string('stock_sync_status', 32)->nullable()->after('stock_synced_at')->index();
        });

        Schema::create('contapyme_sync_runs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('origin', 24)->default('manual')->index();
            $table->string('mode', 24)->default('full');
            $table->string('status', 24)->default('queued')->index();
            $table->string('warehouse', 80)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->unsignedInteger('processed')->default(0);
            $table->unsignedInteger('updated')->default(0);
            $table->unsignedInteger('unchanged')->default(0);
            $table->unsignedInteger('no_sku')->default(0);
            $table->unsignedInteger('confirmed_zero')->default(0);
            $table->unsignedInteger('missing_contapyme')->default(0);
            $table->unsignedInteger('unmapped')->default(0);
            $table->unsignedInteger('skipped_variants')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->text('summary')->nullable();
            $table->json('error_groups')->nullable();
            $table->json('error_details')->nullable();
            $table->json('diagnostics')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contapyme_sync_runs');

        Schema::table('product_variants', function (Blueprint $table): void {
            $table->dropColumn(['stock_sync_status', 'stock_synced_at']);
        });
    }
};
