<?php

namespace App\Observers;

use App\Models\Movement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MovementObserver
{
    const RECORDS_PER_PAGE = 10;
    const MAX_PAGES = 20;
    const MAX_RECORDS = self::RECORDS_PER_PAGE * self::MAX_PAGES; // 200 registros

    /**
     * Handle the Movement "created" event.
     */
    public function created(Movement $movement): void
    {
        // Verificar si es necesario limpiar registros antiguos
        $this->cleanOldRecordsIfNeeded();
    }

    /**
     * Limpia registros antiguos si se excede el límite
     */
    private function cleanOldRecordsIfNeeded(): void
    {
        try {
            $totalRecords = DB::table('movements')->count();
            
            if ($totalRecords > self::MAX_RECORDS) {
                $recordsToDelete = $totalRecords - self::MAX_RECORDS;
                
                // Obtener el ID del último registro que se debe mantener
                $lastIdToKeep = DB::table('movements')
                    ->orderBy('created_at', 'desc')
                    ->orderBy('id', 'desc')
                    ->skip(self::MAX_RECORDS - 1)
                    ->value('id');
                
                if ($lastIdToKeep) {
                    $deleted = DB::table('movements')
                        ->where('id', '<', $lastIdToKeep)
                        ->delete();
                    
                    Log::info("Limpieza automática de movements: {$deleted} registros eliminados");
                }
            }
        } catch (\Exception $e) {
            Log::error("Error en limpieza automática de movements: " . $e->getMessage());
        }
    }
}
