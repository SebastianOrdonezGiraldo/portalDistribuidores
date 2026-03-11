<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->string('slug', 140)->unique();
            $table->timestamps();
        });

        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_attribute_id')->constrained()->cascadeOnDelete();
            $table->string('value', 120);
            $table->string('slug', 140);
            $table->timestamps();

            $table->unique(['product_attribute_id', 'slug'], 'product_attribute_values_unique');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('variant_attribute_id')
                ->nullable()
                ->after('category_id')
                ->constrained('product_attributes')
                ->nullOnDelete();
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_attribute_value_id')->constrained()->restrictOnDelete();
            $table->decimal('price', 12, 2);
            $table->decimal('stock', 12, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(1);
            $table->timestamps();

            $table->unique(['product_id', 'product_attribute_value_id'], 'product_variants_unique');
            $table->index(['product_id', 'is_active']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('product_variant_id')
                ->nullable()
                ->after('product_id')
                ->constrained('product_variants')
                ->nullOnDelete();
            $table->string('variant_attribute_snapshot', 120)->nullable()->after('sku_snapshot');
            $table->string('variant_value_snapshot', 120)->nullable()->after('variant_attribute_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_variant_id');
            $table->dropColumn(['variant_attribute_snapshot', 'variant_value_snapshot']);
        });

        Schema::dropIfExists('product_variants');

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('variant_attribute_id');
        });

        Schema::dropIfExists('product_attribute_values');
        Schema::dropIfExists('product_attributes');
    }
};

