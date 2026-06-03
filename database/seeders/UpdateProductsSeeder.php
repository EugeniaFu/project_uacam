<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Actualizar algunos productos para que tengan bajo stock
        DB::update('UPDATE products SET quantity = 2, min_quantity = 5 WHERE id = 1');
        DB::update('UPDATE products SET quantity = 1, min_quantity = 3 WHERE id = 2');
        DB::update('UPDATE products SET quantity = 4, min_quantity = 6 WHERE id = 6');
        DB::update('UPDATE products SET quantity = 3, min_quantity = 5 WHERE id = 7');
        DB::update('UPDATE products SET quantity = 2, min_quantity = 4 WHERE id = 8');
        
        echo "Productos actualizados con bajo stock\n";
    }
}
