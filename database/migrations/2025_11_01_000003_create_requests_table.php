<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class CreateRequestsTable extends Migration {
    public function up(){ Schema::create('requests', function(Blueprint $table){ $table->id(); $table->string('folio'); $table->enum('type',['prestamo','salida']); $table->string('solicitante'); $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); $table->enum('status',['pendiente','activa','devuelta','cancelada'])->default('pendiente'); $table->date('return_date')->nullable(); $table->timestamps(); }); }
    public function down(){ Schema::dropIfExists('requests'); }
}
