<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearTestData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:test-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpiar todos los datos de prueba excepto users y categories';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->confirm('¿Está seguro de que desea eliminar todos los datos de prueba? Esta acción no se puede deshacer.')) {
            $this->info('Operación cancelada.');
            return 0;
        }

        $this->info('Limpiando datos de prueba...');

        try {
            // Deshabilitar verificación de claves foráneas
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // Limpiar tablas en orden correcto para evitar problemas de claves foráneas
            DB::table('movements')->truncate();
            $this->info('✓ Tabla movements limpiada');

            DB::table('request_items')->truncate();
            $this->info('✓ Tabla request_items limpiada');

            DB::table('requests')->truncate();
            $this->info('✓ Tabla requests limpiada');

            DB::table('products')->truncate();
            $this->info('✓ Tabla products limpiada');

            DB::table('password_reset_tokens')->truncate();
            $this->info('✓ Tabla password_reset_tokens limpiada');

            DB::table('failed_jobs')->truncate();
            $this->info('✓ Tabla failed_jobs limpiada');

            DB::table('personal_access_tokens')->truncate();
            $this->info('✓ Tabla personal_access_tokens limpiada');

            // Habilitar verificación de claves foráneas
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            $this->info('');
            $this->info('✅ Datos de prueba eliminados exitosamente.');
            $this->info('Las tablas users y categories se mantuvieron intactas.');

            return 0;
        } catch (\Exception $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            $this->error('Error al limpiar los datos: ' . $e->getMessage());
            return 1;
        }
    }
}
