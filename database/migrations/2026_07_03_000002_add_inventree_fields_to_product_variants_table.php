<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table): void {
            $table->decimal('external_stock', 12, 2)->nullable()->after('stock');
            $table->decimal('reserved_stock', 12, 2)->default(0)->after('external_stock');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table): void {
            $table->dropColumn(['external_stock', 'reserved_stock']);
        });
    }
};
