<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('list_id')->constrained('company_lists')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            // Snapshots para mostrar incluso si el producto se elimina
            $table->string('product_name_snapshot');
            $table->string('sku_snapshot');
            $table->string('variant_value_snapshot')->nullable();
            $table->unsignedSmallInteger('qty')->default(1);
            $table->string('unit_label', 40)->default('unidad');
            $table->timestamps();

            $table->index('list_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_list_items');
    }
};
