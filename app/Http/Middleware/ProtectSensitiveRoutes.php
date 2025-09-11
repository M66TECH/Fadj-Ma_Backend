<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProtectSensitiveRoutes
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
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
        
        // Seuls les administrateurs peuvent accéder aux routes sensibles
        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé. Seuls les administrateurs peuvent effectuer cette action.',
                'error' => 'ADMIN_REQUIRED'
            ], 403);
        }

        return $next($request);
    }
}
