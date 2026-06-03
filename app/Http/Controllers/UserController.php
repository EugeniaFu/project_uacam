<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserController extends Controller {
    public function index(){ 
        return response()->json(
            User::all(['id', 'name', 'email', 'role', 'active', 'created_at'])
        );
    }
    
    public function store(Request $r){ 
        $validated = $r->validate([
            'name' => 'required|string|min:2|max:255|unique:users',
            'email' => 'required|email|unique:users',
            'role' => 'required|in:encargado,coordinador,admin',
            'password' => 'required|string|min:4'
        ]);
        
        $validated['password'] = Hash::make($validated['password']);
        $validated['active'] = true;
        
        $u = User::create($validated);
        
        // Registrar movimiento
        DB::table('movements')->insert([
            'user_id' => $r->user()->id ?? null,
            'action' => 'create_user',
            'details' => 'Creó usuario: ' . $validated['name'] . ' (' . $validated['role'] . ')',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        return response()->json($u, 201);
    }
    
    public function update(Request $r, $id){ 
        $u = User::findOrFail($id);
        
        // Debug: Ver qué llega
        \Log::info('Update user request', [
            'user_id' => $id,
            'has_password' => $r->has('password'),
            'filled_password' => $r->filled('password'),
            'password_length' => $r->has('password') ? strlen($r->password) : 0,
            'all_data' => $r->except('password')
        ]);
        
        $validated = $r->validate([
            'name' => 'sometimes|string|min:2|max:255|unique:users,name,' . $id,
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'role' => 'sometimes|in:encargado,coordinador,admin',
            'password' => 'sometimes|nullable|string|min:4'
        ]);
        
        // Hashear la contraseña si fue proporcionada
        if(isset($validated['password']) && !empty($validated['password'])){
            \Log::info('Hasheando contraseña para usuario ' . $id);
            $validated['password'] = Hash::make($validated['password']);
        } else {
            // Si no se proporciona contraseña, eliminar del array para no actualizarla
            unset($validated['password']);
            \Log::info('No se actualiza contraseña para usuario ' . $id);
        }
        
        $oldName = $u->name;
        $u->update($validated);
        
        // Registrar movimiento
        $changes = [];
        if(isset($validated['name']) && $validated['name'] != $oldName) $changes[] = 'nombre';
        if(isset($validated['email'])) $changes[] = 'email';
        if(isset($validated['role'])) $changes[] = 'rol';
        if(isset($validated['password'])) $changes[] = 'contraseña';
        
        $details = 'Editó usuario: ' . $oldName;
        if(!empty($changes)) $details .= ' (cambios: ' . implode(', ', $changes) . ')';
        
        DB::table('movements')->insert([
            'user_id' => $r->user()->id ?? null,
            'action' => 'edit_user',
            'details' => $details,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        return response()->json($u);
    }
    
    public function toggle(Request $r, $id){ 
        $u = User::findOrFail($id);
        $u->active = !$u->active;
        $u->save();
        
        // Registrar movimiento
        $action = $u->active ? 'activate_user' : 'deactivate_user';
        $status = $u->active ? 'activó' : 'desactivó';
        
        DB::table('movements')->insert([
            'user_id' => $r->user()->id ?? null,
            'action' => $action,
            'details' => ucfirst($status) . ' usuario: ' . $u->name,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        return response()->json($u);
    }
}
