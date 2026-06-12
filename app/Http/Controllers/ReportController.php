<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facades\Pdf;

class ReportController extends Controller {
    public function movementsPdf(Request $r){
        $validated = $r->validate([
            'page' => 'sometimes|integer|min:1',
            'only_inventory' => 'sometimes|boolean'
        ]);
        
        $page = $validated['page'] ?? 1;
        $onlyInventory = (bool)($validated['only_inventory'] ?? false);
        $per = 30;
        $offset = ($page - 1) * $per;

        $where = '';
        $params = [];
        if ($onlyInventory) {
            $where = "WHERE m.action IN ('inventory_alta','inventory_baja','create_product','update_product','delete_product','edit_product')";
        }

        $rows = DB::select(
            "SELECT m.*, u.name as user, p.name as product_name
             FROM movements m
             LEFT JOIN users u ON u.id=m.user_id
             LEFT JOIN products p ON p.id=m.product_id
             $where
             ORDER BY m.created_at DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$per, $offset])
        );
        
        $pdf = Pdf::loadView('movements', ['rows' => $rows, 'page' => $page, 'onlyInventory' => $onlyInventory]);
        return $pdf->download('movimientos_page_' . $page . '.pdf');
    }
    
    public function requestsPdf(Request $r){
        $rows = DB::select(
            'SELECT r.*, u.name as user FROM requests r LEFT JOIN users u ON u.id=r.user_id ORDER BY r.created_at DESC'
        );
        $pdf = Pdf::loadView('requests', ['rows' => $rows]);
        return $pdf->download('solicitudes.pdf');
    }
}
