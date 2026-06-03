<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MovementController extends Controller {
    public function index(Request $r){
        $validated = $r->validate([
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:10|max:100'
        ]);
        
        $page = $validated['page'] ?? 1;
        $per = $validated['per_page'] ?? 30;
        $offset = ($page - 1) * $per;
        
        $rows = DB::select(
            'SELECT m.*, u.name as user FROM movements m LEFT JOIN users u ON u.id=m.user_id ORDER BY m.created_at DESC LIMIT ? OFFSET ?',
            [$per, $offset]
        );
        
        $total = DB::select('SELECT COUNT(*) as count FROM movements')[0]->count ?? 0;
        
        return response()->json([
            'data' => $rows,
            'page' => $page,
            'per_page' => $per,
            'total' => $total,
            'pages' => ceil($total / $per)
        ]);
    }
}
