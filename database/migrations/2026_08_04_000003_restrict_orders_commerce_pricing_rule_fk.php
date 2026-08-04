<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['commerce_pricing_rule_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('commerce_pricing_rule_id')
                ->references('id')
                ->on('commerce_pricing_rules')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['commerce_pricing_rule_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('commerce_pricing_rule_id')
                ->references('id')
                ->on('commerce_pricing_rules')
                ->nullOnDelete();
        });
    }
};
