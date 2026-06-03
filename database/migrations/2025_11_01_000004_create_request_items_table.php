<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class CreateRequestItemsTable extends Migration {
    public function up(){ Schema::create('request_items', function(Blueprint $table){ $table->id(); $table->foreignId('request_id')->constrained('requests')->onDelete('cascade'); $table->foreignId('product_id')->constrained('products')->onDelete('cascade'); $table->integer('quantity')->default(1); $table->boolean('returned')->default(false); $table->timestamps(); }); }
    public function down(){ Schema::dropIfExists('request_items'); }
}
