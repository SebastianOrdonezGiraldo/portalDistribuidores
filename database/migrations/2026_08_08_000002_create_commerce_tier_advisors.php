<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_tier_advisors', function (Blueprint $table): void {
            $table->id();
            $table->string('tier', 16)->unique();
            $table->string('advisor_name', 120);
            $table->string('advisor_email', 120);
            $table->string('advisor_whatsapp', 15);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_tier_advisors');
    }
};
