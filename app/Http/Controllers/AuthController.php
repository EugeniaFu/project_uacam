<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller {
    public function checkAccount(Request $r){
        $validated = $r->validate([
            'user' => 'required|string|min:2|max:255'
        ]);
        
        $userInput = $validated['user'];
        $throttleKey = 'login:' . strtolower($userInput) . '|' . $r->ip();
        
        // Verificar si está bloqueado
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $minutes = ceil($seconds / 60);
            
            return response()->json([
                'exists' => false,
                'locked' => true,
                'message' => "Demasiados intentos fallidos. Cuenta bloqueada por {$minutes} minutos.",
                'retry_after' => $seconds,
                'locked_until' => now()->addSeconds($seconds)->toDateTimeString()
            ], 429);
        }
        
        // Buscar por email o nombre (case-insensitive)
        $user = User::whereRaw('LOWER(email) = ? OR LOWER(name) = ?', [
            strtolower($userInput),
            strtolower($userInput)
        ])->first();
        
        // Verificar que existe y está activo
        $exists = $user && $user->active;
        
        return response()->json(['exists' => $exists, 'debug' => ['found' => (bool)$user, 'active' => $user?->active]]);
    }
    
    public function login(Request $r){
        $validated = $r->validate([
            'user' => 'required|string|min:2|max:255',
            'password' => 'required|string|min:4'
        ]);
        
        $userInput = $validated['user'];
        $throttleKey = 'login:' . strtolower($userInput) . '|' . $r->ip();
        
        // Verificar si está bloqueado ANTES de validar credenciales
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $minutes = ceil($seconds / 60);
            
            return response()->json([
                'message' => "Demasiados intentos fallidos. Cuenta bloqueada por {$minutes} minutos.",
                'retry_after' => $seconds,
                'locked_until' => now()->addSeconds($seconds)->toDateTimeString()
            ], 429);
        }
        
        // Buscar por email o nombre (case-insensitive)
        $user = User::whereRaw('LOWER(email) = ? OR LOWER(name) = ?', [
            strtolower($userInput),
            strtolower($userInput)
        ])->first();
        
        if(!$user) {
            // Incrementar intentos fallidos
            RateLimiter::hit($throttleKey, 1200); // 1200 segundos = 20 minutos
            
            $attempts = RateLimiter::attempts($throttleKey);
            $remaining = 5 - $attempts;
            
            return response()->json([
                'message' => 'Credenciales incorrectas',
                'attempts_remaining' => max(0, $remaining)
            ], 404);
        }
        
        if(!$user->active) {
            return response()->json(['message' => 'Usuario desactivado'], 403);
        }
        
        if(!Hash::check($validated['password'], $user->password)) {
            // Incrementar intentos fallidos (20 minutos de bloqueo)
            RateLimiter::hit($throttleKey, 1200);
            
            $attempts = RateLimiter::attempts($throttleKey);
            $remaining = 5 - $attempts;
            
            return response()->json([
                'message' => 'Credenciales incorrectas',
                'attempts_remaining' => max(0, $remaining)
            ], 401);
        }
        
        // Login exitoso: limpiar intentos fallidos
        RateLimiter::clear($throttleKey);
        
        // Mantener tokens anteriores para que el usuario permanezca autenticado
        // hasta que cierre sesión explícitamente. No eliminamos tokens aquí.
        $token = $user->createToken('api-token')->plainTextToken;
        
        return response()->json([
            'token' => $token,
            'role' => $user->role,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ]
        ]);
    }
    
    public function logout(Request $r){
        $user = $r->user();
        
        if($user) {
            // Eliminar el token actual
            $user->currentAccessToken()->delete();
            
            // Opcional: eliminar todos los tokens del usuario (para mayor seguridad)
            // Descomenta la siguiente línea si quieres cerrar sesión en todos los dispositivos
            // $user->tokens()->delete();
        }
        
        // Limpiar la sesión de la aplicación
        $r->session()->invalidate();
        $r->session()->regenerateToken();
        
        return response()->json(['logout' => true, 'message' => 'Logged out successfully']);
    }
}
