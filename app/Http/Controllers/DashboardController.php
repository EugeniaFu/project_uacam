<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller {
    public function summary(Request $r){
        try {
            Log::info('Dashboard summary called', ['user_id' => $r->user()->id ?? 'no user']);
            
            // Obtener solicitudes pendientes con productos concatenados
            $pending = DB::select("SELECT r.id, r.folio, r.solicitante, r.return_date, 
                GROUP_CONCAT(p.name SEPARATOR ', ') as product 
                FROM requests r 
                JOIN request_items ri ON ri.request_id = r.id 
                JOIN products p ON p.id = ri.product_id 
                WHERE r.type = 'prestamo' AND r.status = 'pendiente' 
                GROUP BY r.id, r.folio, r.solicitante, r.return_date 
                ORDER BY r.return_date ASC");
            
            Log::info('Pending requests', ['count' => count($pending)]);
            
            $low = DB::select('SELECT id, name, quantity, min_quantity FROM products WHERE quantity > 0 AND quantity <= min_quantity ORDER BY quantity ASC LIMIT 10');
            
            Log::info('Low stock products', ['count' => count($low)]);
            
            $out = DB::select('SELECT id,name FROM products WHERE quantity=0 LIMIT 10');
            
            Log::info('Out of stock products', ['count' => count($out)]);
            
            $user = $r->user();
            
            $response = [
                'pending' => $pending,
                'low' => $low,
                'out' => $out,
                'role' => $user->role ?? 'encargado',
                'user' => [
                    'name' => $user?->name ?? ''
                ]
            ];
            
            Log::info('Dashboard response', ['data' => $response]);
            
            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Dashboard error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Error al cargar dashboard: ' . $e->getMessage()], 500);
        }
    }
}
