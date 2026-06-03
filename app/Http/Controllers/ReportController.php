<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facades\Pdf;

class ReportController extends Controller {
    public function movementsPdf(Request $r){
        $validated = $r->validate([
            'page' => 'sometimes|integer|min:1'
        ]);
        
        $page = $validated['page'] ?? 1;
        $per = 30;
        $offset = ($page - 1) * $per;
        
        $rows = DB::select(
            'SELECT m.*, u.name as user FROM movements m LEFT JOIN users u ON u.id=m.user_id ORDER BY m.created_at DESC LIMIT ? OFFSET ?',
            [$per, $offset]
        );
        
        $pdf = Pdf::loadView('reports.movements', ['rows' => $rows, 'page' => $page]);
        return $pdf->download('movimientos_page_' . $page . '.pdf');
    }
    
    public function requestsPdf(Request $r){
        $rows = DB::select(
            'SELECT r.*, u.name as user FROM requests r LEFT JOIN users u ON u.id=r.user_id ORDER BY r.created_at DESC'
        );
        $pdf = Pdf::loadView('reports.requests', ['rows' => $rows]);
        return $pdf->download('solicitudes.pdf');
    }
}
