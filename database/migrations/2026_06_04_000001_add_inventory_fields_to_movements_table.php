<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movements', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->index()->after('user_id');
            $table->integer('delta')->nullable()->after('action');
            $table->integer('before_quantity')->nullable()->after('delta');
            $table->integer('after_quantity')->nullable()->after('before_quantity');
            $table->text('alta_observaciones')->nullable()->after('details');
            $table->string('baja_motivo', 255)->nullable()->after('alta_observaciones');
        });
    }

    public function down(): void
    {
        Schema::table('movements', function (Blueprint $table) {
            $table->dropIndex(['product_id']);
            $table->dropColumn([
                'product_id',
                'delta',
                'before_quantity',
                'after_quantity',
                'alta_observaciones',
                'baja_motivo',
            ]);
        });
    }
};
