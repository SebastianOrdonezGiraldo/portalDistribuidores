<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contapyme_inventory_mappings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
            $table->string('irecurso', 160)->unique();
            $table->string('status', 32)->default('pending')->index();
            $table->timestamp('last_validated_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique('product_id', 'contapyme_mapping_product_unique');
            $table->unique('product_variant_id', 'contapyme_mapping_variant_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contapyme_inventory_mappings');
    }
};
