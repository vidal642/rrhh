<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarRol
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $userRol = strtolower($request->user()?->rol ?? '');
        $allowedRoles = array_map('strtolower', $roles);
        
        if (in_array('administrador', $allowedRoles) && !in_array('admin', $allowedRoles)) {
            $allowedRoles[] = 'admin';
        }

        if (! $request->user() || ! in_array($userRol, $allowedRoles)) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'Acceso denegado. No tienes permisos para realizar esta acción.'
            ], 403);
        }

        return $next($request);
    }
}
