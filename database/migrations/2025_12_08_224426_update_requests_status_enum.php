<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Cambiar el enum para incluir 'aprobada'
        DB::statement("ALTER TABLE requests MODIFY COLUMN status ENUM('pendiente', 'activa', 'aprobada', 'devuelta', 'cancelada') DEFAULT 'pendiente'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir al enum original
        DB::statement("ALTER TABLE requests MODIFY COLUMN status ENUM('pendiente', 'activa', 'devuelta', 'cancelada') DEFAULT 'pendiente'");
    }
};
