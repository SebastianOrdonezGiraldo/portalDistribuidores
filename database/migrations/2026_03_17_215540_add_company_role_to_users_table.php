<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Rol interno dentro de la empresa: admin_empresa | usuario_comercial | solo_lectura
            // Nullable: users admin global no tienen company_role
            $table->string('company_role')->nullable()->default(null)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('company_role');
        });
    }
};
