<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MovementController extends Controller {
    public function index(Request $r){
        $validated = $r->validate([
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:10|max:100',
            'only_inventory' => 'sometimes|boolean'
        ]);
        
        $page = $validated['page'] ?? 1;
        $per = $validated['per_page'] ?? 30;
        $onlyInventory = (bool)($validated['only_inventory'] ?? false);
        $offset = ($page - 1) * $per;

        $where = '';
        if ($onlyInventory) {
            $where = "WHERE m.action IN ('inventory_alta','inventory_baja')";
        }
        
        $rows = DB::select(
            "SELECT m.*, u.name as user, p.name as product_name
             FROM movements m
             LEFT JOIN users u ON u.id=m.user_id
             LEFT JOIN products p ON p.id=m.product_id
             $where
             ORDER BY m.created_at DESC
             LIMIT ? OFFSET ?",
            [$per, $offset]
        );

        $totalSql = 'SELECT COUNT(*) as count FROM movements m ' . $where;
        $total = DB::select($totalSql)[0]->count ?? 0;
        
        return response()->json([
            'data' => $rows,
            'page' => $page,
            'per_page' => $per,
            'total' => $total,
            'pages' => ceil($total / $per)
        ]);
    }
}
