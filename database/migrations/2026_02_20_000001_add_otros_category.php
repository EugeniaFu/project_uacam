<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $exists = DB::table('categories')->where('name', 'Otros')->exists();

        if (! $exists) {
            DB::table('categories')->insert([
                'name' => 'Otros',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('categories')->where('name', 'Otros')->delete();
    }
};
