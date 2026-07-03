<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // No se hace backfill automático.
        // inventree_stock solo lo establece la sincronización activa con InvenTree.
        // Los productos legacy (no sincronizados) mantienen inventree_stock = null
        // y continúan funcionando con el sistema de stock tradicional.
    }

    public function down(): void
    {
        // Nada que revertir
    }
};
