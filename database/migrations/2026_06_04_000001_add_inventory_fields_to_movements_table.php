<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movements', function (Blueprint $table) {

            // Si aún no existe product_id
            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products')
                ->nullOnDelete();

            $table->string('inventory_type', 10)->nullable()->after('product_id');

            // Equivalentes más descriptivos
            $table->integer('quantity_change')->nullable()->after('inventory_type');
            $table->integer('quantity_before')->nullable()->after('quantity_change');
            $table->integer('quantity_after')->nullable()->after('quantity_before');

            $table->text('alta_observaciones')->nullable()->after('details');
            $table->string('baja_motivo', 255)->nullable()->after('alta_observaciones');
            $table->text('baja_observaciones')->nullable()->after('baja_motivo');
        });
    }

    public function down(): void
    {
        Schema::table('movements', function (Blueprint $table) {

            $table->dropColumn([
                'inventory_type',
                'quantity_before',
                'quantity_after',
                'quantity_change',
                'alta_observaciones',
                'baja_motivo',
                'baja_observaciones',
            ]);

            $table->dropConstrainedForeignId('product_id');
        });
    }
};