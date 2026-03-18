<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distributor_id')->constrained('distributors')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('address', 180)->nullable();
            $table->string('city', 120)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index('distributor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_branches');
    }
};
