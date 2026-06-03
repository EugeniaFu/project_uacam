<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanOldRecords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'records:clean {--dry-run : Simular la limpieza sin eliminar registros}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpia registros antiguos de movements y requests manteniendo solo 20 páginas (200 registros)';

    /**
     * Número de registros por página
     */
    const RECORDS_PER_PAGE = 10;
    
    /**
     * Número máximo de páginas a mantener
     */
    const MAX_PAGES = 20;
    
    /**
     * Número máximo de registros a mantener
     */
    const MAX_RECORDS = self::RECORDS_PER_PAGE * self::MAX_PAGES; // 200 registros

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->info('Modo simulación activado - No se eliminarán registros');
        }
        
        $this->info('Iniciando limpieza de registros antiguos...');
        
        // Limpiar movements
        $this->cleanMovements($isDryRun);
        
        // Limpiar requests (solo las completadas/devueltas)
        $this->cleanRequests($isDryRun);
        
        $this->info('Limpieza completada exitosamente.');
        
        return 0;
    }
    
    /**
     * Limpia registros antiguos de la tabla movements
     */
    private function cleanMovements($isDryRun = false)
    {
        $this->info('Verificando tabla movements...');
        
        $totalRecords = DB::table('movements')->count();
        $this->info("Total de registros en movements: {$totalRecords}");
        
        if ($totalRecords <= self::MAX_RECORDS) {
            $this->info('No es necesario eliminar registros de movements.');
            return;
        }
        
        $recordsToDelete = $totalRecords - self::MAX_RECORDS;
        
        // Obtener el ID del último registro que se debe mantener
        $lastIdToKeep = DB::table('movements')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->skip(self::MAX_RECORDS - 1)
            ->value('id');
        
        if ($isDryRun) {
            $this->warn("Se eliminarían {$recordsToDelete} registros de movements (IDs menores a {$lastIdToKeep})");
        } else {
            DB::beginTransaction();
            try {
                $deleted = DB::table('movements')
                    ->where('id', '<', $lastIdToKeep)
                    ->delete();
                
                DB::commit();
                
                $this->info("✓ Eliminados {$deleted} registros antiguos de movements");
                Log::info("Limpieza de movements completada: {$deleted} registros eliminados");
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Error al eliminar registros de movements: " . $e->getMessage());
                Log::error("Error en limpieza de movements: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Limpia registros antiguos de la tabla requests (solo completadas/devueltas)
     */
    private function cleanRequests($isDryRun = false)
    {
        $this->info('Verificando tabla requests...');
        
        // Solo contar solicitudes completadas (devueltas o canceladas)
        $totalRecords = DB::table('requests')
            ->whereIn('status', ['devuelta', 'cancelada'])
            ->count();
        
        $this->info("Total de solicitudes completadas: {$totalRecords}");
        
        if ($totalRecords <= self::MAX_RECORDS) {
            $this->info('No es necesario eliminar registros de requests.');
            return;
        }
        
        $recordsToDelete = $totalRecords - self::MAX_RECORDS;
        
        // Obtener el ID del último registro completado que se debe mantener
        $lastIdToKeep = DB::table('requests')
            ->whereIn('status', ['devuelta', 'cancelada'])
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->skip(self::MAX_RECORDS - 1)
            ->value('id');
        
        if ($isDryRun) {
            $this->warn("Se eliminarían {$recordsToDelete} solicitudes completadas (IDs menores a {$lastIdToKeep})");
        } else {
            DB::beginTransaction();
            try {
                // Primero eliminar los items de las solicitudes
                $requestIdsToDelete = DB::table('requests')
                    ->whereIn('status', ['devuelta', 'cancelada'])
                    ->where('id', '<', $lastIdToKeep)
                    ->pluck('id');
                
                if ($requestIdsToDelete->isNotEmpty()) {
                    $deletedItems = DB::table('request_items')
                        ->whereIn('request_id', $requestIdsToDelete)
                        ->delete();
                    
                    $this->info("✓ Eliminados {$deletedItems} items de solicitudes");
                    
                    // Luego eliminar las solicitudes
                    $deleted = DB::table('requests')
                        ->whereIn('status', ['devuelta', 'cancelada'])
                        ->where('id', '<', $lastIdToKeep)
                        ->delete();
                    
                    $this->info("✓ Eliminadas {$deleted} solicitudes completadas antiguas");
                    Log::info("Limpieza de requests completada: {$deleted} registros eliminados");
                }
                
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Error al eliminar registros de requests: " . $e->getMessage());
                Log::error("Error en limpieza de requests: " . $e->getMessage());
            }
        }
    }
}
