<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['distributor_id']);
            $table->dropForeign(['user_id']);

            $table->unsignedBigInteger('distributor_id')->nullable()->change();
            $table->unsignedBigInteger('user_id')->nullable()->change();

            $table->foreign('distributor_id')->references('id')->on('distributors')->restrictOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['distributor_id']);
            $table->dropForeign(['user_id']);

            $table->unsignedBigInteger('distributor_id')->nullable(false)->change();
            $table->unsignedBigInteger('user_id')->nullable(false)->change();

            $table->foreign('distributor_id')->references('id')->on('distributors')->restrictOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
        });
    }
};
