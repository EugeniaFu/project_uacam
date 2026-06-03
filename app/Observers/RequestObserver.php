<?php

namespace App\Observers;

use App\Models\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RequestObserver
{
    const RECORDS_PER_PAGE = 10;
    const MAX_PAGES = 20;
    const MAX_RECORDS = self::RECORDS_PER_PAGE * self::MAX_PAGES; // 200 registros

    /**
     * Handle the Request "updated" event.
     * Se ejecuta cuando una solicitud cambia de estado a devuelta o cancelada
     */
    public function updated(Request $request): void
    {
        // Solo limpiar cuando el estado cambia a devuelta o cancelada
        if (in_array($request->status, ['devuelta', 'cancelada'])) {
            $this->cleanOldRecordsIfNeeded();
        }
    }

    /**
     * Limpia solicitudes completadas antiguas si se excede el límite
     */
    private function cleanOldRecordsIfNeeded(): void
    {
        try {
            // Solo contar solicitudes completadas
            $totalRecords = DB::table('requests')
                ->whereIn('status', ['devuelta', 'cancelada'])
                ->count();
            
            if ($totalRecords > self::MAX_RECORDS) {
                $recordsToDelete = $totalRecords - self::MAX_RECORDS;
                
                // Obtener el ID del último registro completado que se debe mantener
                $lastIdToKeep = DB::table('requests')
                    ->whereIn('status', ['devuelta', 'cancelada'])
                    ->orderBy('created_at', 'desc')
                    ->orderBy('id', 'desc')
                    ->skip(self::MAX_RECORDS - 1)
                    ->value('id');
                
                if ($lastIdToKeep) {
                    DB::beginTransaction();
                    try {
                        // Obtener IDs de solicitudes a eliminar
                        $requestIdsToDelete = DB::table('requests')
                            ->whereIn('status', ['devuelta', 'cancelada'])
                            ->where('id', '<', $lastIdToKeep)
                            ->pluck('id');
                        
                        if ($requestIdsToDelete->isNotEmpty()) {
                            // Primero eliminar items relacionados
                            DB::table('request_items')
                                ->whereIn('request_id', $requestIdsToDelete)
                                ->delete();
                            
                            // Luego eliminar las solicitudes
                            $deleted = DB::table('requests')
                                ->whereIn('status', ['devuelta', 'cancelada'])
                                ->where('id', '<', $lastIdToKeep)
                                ->delete();
                            
                            DB::commit();
                            Log::info("Limpieza automática de requests: {$deleted} registros eliminados");
                        }
                    } catch (\Exception $e) {
                        DB::rollBack();
                        throw $e;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Error en limpieza automática de requests: " . $e->getMessage());
        }
    }
}
