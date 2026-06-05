<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class ProductController extends Controller {
    public function index(){ 
        return response()->json(
            Product::with('category')->get()->map(function($p){ 
                return [
                    'id' => $p->id,
                    'codigo' => $p->codigo,
                    'name' => $p->name,
                    'description' => $p->description,
                    'category_id' => $p->category_id,
                    'category_name' => $p->category ? $p->category->name : '',
                    'quantity' => $p->quantity,
                    'min_quantity' => $p->min_quantity,
                    'tipo' => $p->tipo
                ];
            })->values()->all()
        );
    }
    
    public function store(Request $r){ 
        $validated = $r->validate([
            'name' => 'required|string|min:2|max:255',
            'description' => 'nullable|string|max:1000',
            'category_id' => 'required|integer|exists:categories,id',
            'quantity' => 'required|integer|min:0',
            'min_quantity' => 'required|integer|min:0',
            'tipo' => 'required|in:Préstamo,Salida'
        ]);
        
        $p = Product::create($validated);
        
        DB::table('movements')->insert([
            'user_id' => $r->user()->id ?? null,
            'action' => 'create_product',
            'details' => 'Producto creado: ' . $p->name,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        return response()->json($p, 201);
    }
    
    public function update(Request $r, $id){ 
        $p = Product::findOrFail($id);
        $beforeQty = $p->quantity;

        $validated = $r->validate([
            'name' => 'sometimes|string|min:2|max:255',
            'description' => 'nullable|string|max:1000',
            'category_id' => 'sometimes|integer|exists:categories,id',
            'quantity' => 'sometimes|integer|min:0',
            'min_quantity' => 'sometimes|integer|min:0',
            'tipo' => 'sometimes|in:Préstamo,Salida',
            'altaObservaciones' => 'nullable|string|max:1000',
            'bajaMotivo' => 'nullable|string|max:255',
            'bajaObservaciones' => 'nullable|string|max:1000'
        ]);

        $altaObs = $validated['altaObservaciones'] ?? null;
        $bajaMotivo = $validated['bajaMotivo'] ?? null;
        $bajaObs = $validated['bajaObservaciones'] ?? null;
        $updateData = Arr::except($validated, ['altaObservaciones', 'bajaMotivo', 'bajaObservaciones']);
        $p->update($updateData);
        $afterQty = $p->quantity;
        $delta = $afterQty - $beforeQty;

        if ($delta !== 0 && Schema::hasColumn('movements', 'quantity_change')) {
            $action = $delta > 0 ? 'inventory_alta' : 'inventory_baja';
            $inventoryType = $delta > 0 ? 'alta' : 'baja';
            $details = ($delta > 0 ? 'Alta' : 'Baja') . ' inventario: ' . $p->name . ' (' . $beforeQty . ' -> ' . $afterQty . ')';
            DB::table('movements')->insert([
                'user_id' => $r->user()->id ?? null,
                'product_id' => $p->id,
                'action' => $action,
                'details' => $details,
                'inventory_type' => $inventoryType,
                'quantity_change' => $delta,
                'quantity_before' => $beforeQty,
                'quantity_after' => $afterQty,
                'alta_observaciones' => $delta > 0 ? $altaObs : null,
                'baja_motivo' => $delta < 0 ? $bajaMotivo : null,
                'baja_observaciones' => $delta < 0 ? $bajaObs : null,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } else {
            DB::table('movements')->insert([
                'user_id' => $r->user()->id ?? null,
                'action' => 'update_product',
                'details' => 'Producto actualizado: ' . $p->name,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        return response()->json($p);
    }
    
    public function destroy(Request $r, $id){ 
        $p = Product::findOrFail($id);
        $productName = $p->name;
        $p->delete();
        DB::table('movements')->insert([
            'user_id' => $r->user()->id ?? null,
            'action' => 'delete_product',
            'details' => 'Eliminado: ' . $productName,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        return response()->json(['deleted' => true]);
    }
}