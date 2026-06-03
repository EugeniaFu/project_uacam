<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class LoginRateLimiter
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->throttleKey($request);
        
        // Verificar si está bloqueado
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            $minutes = ceil($seconds / 60);
            
            return response()->json([
                'message' => "Demasiados intentos fallidos. Cuenta bloqueada por {$minutes} minutos.",
                'retry_after' => $seconds,
                'locked_until' => now()->addSeconds($seconds)->toDateTimeString()
            ], 429);
        }
        
        return $next($request);
    }
    
    /**
     * Get the throttle key for the given request.
     */
    protected function throttleKey(Request $request): string
    {
        $user = strtolower($request->input('user', ''));
        return 'login:' . $user . '|' . $request->ip();
    }
}
