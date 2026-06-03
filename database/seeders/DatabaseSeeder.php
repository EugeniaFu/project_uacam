<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
class DatabaseSeeder extends Seeder {
    public function run(){
        DB::table('users')->insert([
            ['name'=>'Coordinador Prueba','email'=>'coord@example.com','password'=>Hash::make('password'),'role'=>'coordinador','active'=>1,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Encargado Uno','email'=>'enc1@example.com','password'=>Hash::make('password'),'role'=>'encargado','active'=>1,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Hugo Coh','email'=>'hugo@uac.edu','password'=>Hash::make('password'),'role'=>'coordinador','active'=>1,'created_at'=>now(),'updated_at'=>now()]
        ]);
        DB::table('categories')->insert([['name'=>'Limpieza'],['name'=>'computo'],['name'=>'cableado'],['name'=>'cofee brake'],['name'=>'Otros']]);
        DB::table('products')->insert([
            ['name'=>'Cable HDMI','description'=>'Cable HDMI 2m','category_id'=>3,'quantity'=>10,'min_quantity'=>2,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Proyector','description'=>'Proyector 3000 lumens','category_id'=>2,'quantity'=>1,'min_quantity'=>1,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Limpiador','description'=>'Limpiador multiusos','category_id'=>1,'quantity'=>3,'min_quantity'=>2,'created_at'=>now(),'updated_at'=>now()]
        ]);
    }
}
