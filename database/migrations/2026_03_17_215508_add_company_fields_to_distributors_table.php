<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('distributors', function (Blueprint $table) {
            $table->string('nit', 40)->nullable()->after('name');
            $table->string('address', 180)->nullable()->after('nit');
            $table->string('city', 120)->nullable()->after('address');
            $table->string('phone', 40)->nullable()->after('city');
            $table->string('contact_email', 160)->nullable()->after('phone');
            $table->string('contact_name', 120)->nullable()->after('contact_email');
        });
    }

    public function down(): void
    {
        Schema::table('distributors', function (Blueprint $table) {
            $table->dropColumn(['nit', 'address', 'city', 'phone', 'contact_email', 'contact_name']);
        });
    }
};
