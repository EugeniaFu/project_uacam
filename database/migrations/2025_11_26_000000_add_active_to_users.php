<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddActiveToUsers extends Migration {
    public function up(){
        // Si la tabla existe pero sin todas las columnas, las agregamos
        if(Schema::hasTable('users')){
            if(!Schema::hasColumn('users', 'role')){
                Schema::table('users', function(Blueprint $table){
                    $table->enum('role',['coordinador','encargado','admin'])->default('encargado')->after('password');
                });
            }
            if(!Schema::hasColumn('users', 'active')){
                Schema::table('users', function(Blueprint $table){
                    $table->boolean('active')->default(true)->after('role');
                });
            }
        }
    }
    
    public function down(){
        // No hacer nada en el rollback
    }
}

