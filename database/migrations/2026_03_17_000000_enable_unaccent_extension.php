<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS unaccent;');
    }

    public function down(): void
    {
        // La extensión no se elimina en down() para evitar romper
        // otros objetos de la base de datos que puedan depender de ella.
    }
};
