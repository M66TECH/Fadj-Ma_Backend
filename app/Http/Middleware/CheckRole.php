<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // Vérifier si l'utilisateur est authentifié
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié',
                'error' => 'UNAUTHENTICATED'
            ], 401);
        }

        $user = auth()->user();
        
        // Vérifier le rôle
        if ($user->role !== $role) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé. Rôle requis: ' . $role,
                'error' => 'INSUFFICIENT_PERMISSIONS'
            ], 403);
        }

        return $next($request);
    }
}
