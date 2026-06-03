<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RequestController extends Controller {
    public function index(){ 
        $rows = DB::table('requests')->orderBy('created_at','desc')->get(); 
        return response()->json($rows); 
    }
    
    public function store(Request $r){
        \Log::info('Store request called', ['data' => $r->all()]);
        
        // Validación de entrada
        try {
            $validated = $r->validate([
                'type' => 'required|in:prestamo,salida,devolucion',
                'solicitante' => ['required','string','min:2','max:255','regex:/^[\\p{L}\\s]+$/u'],
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|integer|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'return_date' => 'nullable|date_format:Y-m-d'
            ]);
            \Log::info('Validation passed', ['validated' => $validated]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation failed', ['errors' => $e->errors()]);
            throw $e;
        }

        DB::beginTransaction();
        try{
            $folio = 'FOL-'.time();
            
            // Verificar stock disponible
            foreach($validated['items'] as $item){
                $product = DB::table('products')->where('id', $item['product_id'])->first();
                if(!$product || $product->quantity < $item['quantity']){
                    DB::rollBack();
                    return response()->json(['error' => 'Stock insuficiente'], 422);
                }
            }
            
            // Determinar el estado inicial según el tipo de solicitud
            $status = ($validated['type'] === 'salida') ? 'aprobada' : 'pendiente';
            
            $id = DB::table('requests')->insertGetId([
                'folio' => $folio,
                'type' => $validated['type'],
                'solicitante' => $validated['solicitante'],
                'user_id' => $r->user()->id ?? null,
                'status' => $status,
                'return_date' => $validated['return_date'] ?? null,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            foreach($validated['items'] as $item){
                DB::table('request_items')->insert([
                    'request_id' => $id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                DB::table('products')->where('id', $item['product_id'])->decrement('quantity', $item['quantity']);
            }
            
            DB::table('movements')->insert([
                'user_id' => $r->user()->id ?? null,
                'action' => 'create_request',
                'details' => 'Solicitud ' . $folio,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            DB::commit();
            return response()->json(['id' => $id, 'folio' => $folio], 201);
        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['error' => 'Error procesando solicitud: ' . $e->getMessage()], 500);
        }
    }
    
    public function markReturn(Request $r, $id){
        $request = DB::table('requests')->where('id', $id)->first();
        if(!$request){
            return response()->json(['error' => 'Solicitud no encontrada'], 404);
        }
        
        $items = DB::table('request_items')->where('request_id', $id)->get();
        DB::beginTransaction();
        try{
            foreach($items as $it){
                DB::table('products')->where('id', $it->product_id)->increment('quantity', $it->quantity);
                DB::table('request_items')->where('id', $it->id)->update(['returned' => 1]);
            }
            DB::table('requests')->where('id', $id)->update(['status' => 'devuelta']);
            DB::table('movements')->insert([
                'user_id' => $r->user()->id ?? null,
                'action' => 'return_request',
                'details' => 'Devolución solicitud ' . $id,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            DB::commit();
            return response()->json(['returned' => true]);
        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    public function destroy(Request $r, $id){
        $request = DB::table('requests')->where('id', $id)->first();
        if(!$request){
            return response()->json(['error' => 'Solicitud no encontrada'], 404);
        }
        
        DB::beginTransaction();
        try{
            DB::table('requests')->where('id', $id)->delete();
            DB::table('movements')->insert([
                'user_id' => $r->user()->id ?? null,
                'action' => 'delete_request',
                'details' => 'Solicitud eliminada ' . $id,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            DB::commit();
            return response()->json(['deleted' => true]);
        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    public function history(){
        $rows = DB::select(
            'SELECT r.*, u.name as user FROM requests r LEFT JOIN users u ON u.id=r.user_id ORDER BY r.created_at DESC'
        );
        return response()->json($rows);
    }
    
    public function show(Request $r, $id){
        $request = DB::table('requests')
            ->leftJoin('users', 'users.id', '=', 'requests.user_id')
            ->select('requests.*', 'users.name as user')
            ->where('requests.id', $id)
            ->first();
            
        if(!$request){
            return response()->json(['error' => 'Solicitud no encontrada'], 404);
        }
        
        $items = DB::table('request_items')
            ->join('products', 'products.id', '=', 'request_items.product_id')
            ->where('request_items.request_id', $id)
            ->select('request_items.*', 'products.name as product_name')
            ->get();
            
        $request->items = $items;
        
        return response()->json($request);
    }
    
    public function update(Request $r, $id){
        // Validación de entrada
        $validated = $r->validate([
            'solicitante' => ['required','string','min:2','max:255','regex:/^[\\p{L}\\s]+$/u'],
            'return_date' => 'nullable|date_format:Y-m-d',
            'status' => 'nullable|in:pendiente,aprobada,cancelada,devuelta',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1'
        ]);
        
        // Verificar que solo coordinadores puedan editar
        $user = $r->user();
        if(!$user || $user->role !== 'coordinador'){
            return response()->json(['error' => 'No autorizado'], 403);
        }
        
        $request = DB::table('requests')->where('id', $id)->first();
        if(!$request){
            return response()->json(['error' => 'Solicitud no encontrada'], 404);
        }
        
        DB::beginTransaction();
        try{
            // Obtener items actuales para revertir stock
            $currentItems = DB::table('request_items')->where('request_id', $id)->get();
            
            $oldStatus = $request->status;
            $newStatus = $validated['status'] ?? $oldStatus;
            
            // Determinar si necesitamos manejar inventario
            // Estados que devuelven stock: 'devuelta' y 'cancelada'
            $wasReturned = in_array($oldStatus, ['devuelta', 'cancelada']);
            $willBeReturned = in_array($newStatus, ['devuelta', 'cancelada']);
            
            // Si el estado anterior NO devolvía stock, revertir el stock
            if(!$wasReturned){
                foreach($currentItems as $item){
                    DB::table('products')->where('id', $item->product_id)->increment('quantity', $item->quantity);
                }
            }
            
            // Si el nuevo estado NO devuelve stock, verificar y descontar stock
            if(!$willBeReturned){
                // Verificar stock disponible para nuevos items
                foreach($validated['items'] as $item){
                    $product = DB::table('products')->where('id', $item['product_id'])->first();
                    if(!$product || $product->quantity < $item['quantity']){
                        DB::rollBack();
                        return response()->json(['error' => 'Stock insuficiente para el producto: ' . ($product->name ?? $item['product_id'])], 422);
                    }
                }
            }
            
            // Preparar datos para actualizar
            $updateData = [
                'solicitante' => $validated['solicitante'],
                'return_date' => $validated['return_date'] ?? null,
                'status' => $newStatus,
                'updated_at' => now()
            ];
            
            // Actualizar solicitud
            DB::table('requests')->where('id', $id)->update($updateData);
            
            // Eliminar items anteriores
            DB::table('request_items')->where('request_id', $id)->delete();
            
            // Insertar nuevos items
            foreach($validated['items'] as $item){
                DB::table('request_items')->insert([
                    'request_id' => $id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                // Solo descontar stock si NO devuelve stock (no es devuelta ni cancelada)
                if(!$willBeReturned){
                    DB::table('products')->where('id', $item['product_id'])->decrement('quantity', $item['quantity']);
                }
            }
            
            // Registrar movimiento
            $statusInfo = isset($validated['status']) ? ' - Estado: ' . $validated['status'] : '';
            $details = 'Editó solicitud #' . $request->folio . $statusInfo;
            DB::table('movements')->insert([
                'user_id' => $user->id,
                'action' => 'edit_request',
                'details' => $details,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            DB::commit();
            return response()->json(['message' => 'Solicitud actualizada exitosamente']);
        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['error' => 'Error actualizando solicitud: ' . $e->getMessage()], 500);
        }
    }
}
