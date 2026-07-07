<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // No se hace backfill automatico.
        // external_stock solo lo establece una sincronizacion externa activa.
        // Los productos legacy mantienen external_stock = null y continuan
        // funcionando con el sistema de stock tradicional.
    }

    public function down(): void
    {
        // Nada que revertir
    }
};
