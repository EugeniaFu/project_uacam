<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class ValidateSessionAfterLogout
{
    /**
     * Handle an incoming request.
     * 
     * Valida que la sesión siga siendo válida en cada petición.
     * Si el token ha sido revocado (logout), rechaza la petición.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Si el usuario está autenticado, validar que el token sigue siendo válido
        if (Auth::check() && Auth::user()) {
            $user = Auth::user();
            
            // Verificar que el usuario sigue activo
            if (!$user->active) {
                Auth::logout();
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Session invalidated. User is no longer active.',
                        'logout' => true
                    ], 401);
                }
            }
        }
        
        return $next($request);
    }
}
