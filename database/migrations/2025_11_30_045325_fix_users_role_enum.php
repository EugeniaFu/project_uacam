<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Primero, cambiar temporalmente a VARCHAR para permitir cualquier valor
        DB::statement("ALTER TABLE users MODIFY COLUMN role VARCHAR(50) DEFAULT 'encargado'");
        
        // Actualizar registros existentes que tengan 'coordinator' a 'coordinador'
        DB::table('users')->where('role', 'coordinator')->update(['role' => 'coordinador']);
        
        // Ahora cambiar a ENUM con los valores correctos
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('coordinador','encargado','admin') DEFAULT 'encargado'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('coordinator','encargado','admin') DEFAULT 'encargado'");
        DB::table('users')->where('role', 'coordinador')->update(['role' => 'coordinator']);
    }
};
