<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('silver_markup_basis_points');
            $table->unsignedInteger('silver_rounding_multiple');
            $table->foreignId('created_by_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
        });

        if (DB::table('commerce_pricing_rules')->count() === 0) {
            $now = now();

            DB::table('commerce_pricing_rules')->insert([
                'silver_markup_basis_points' => 500,
                'silver_rounding_multiple' => 1000,
                'created_by_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_pricing_rules');
    }
};
