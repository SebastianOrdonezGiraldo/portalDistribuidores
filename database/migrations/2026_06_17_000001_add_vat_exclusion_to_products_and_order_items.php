<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->boolean('is_vat_excluded')->default(false)->after('is_active');
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->boolean('is_vat_excluded_snapshot')->default(false)->after('subtotal');
            $table->decimal('vat_rate_snapshot', 5, 4)->default('0.1300')->after('is_vat_excluded_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropColumn(['is_vat_excluded_snapshot', 'vat_rate_snapshot']);
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('is_vat_excluded');
        });
    }
};
