<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TokenFromQuery
{
    /**
     * Handle an incoming request.
     * Permet de passer le token via query string: ?token=xxx
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Si pas de header Authorization mais token dans query string
        if (!$request->bearerToken() && $request->has('token')) {
            $token = $request->query('token');
            $request->headers->set('Authorization', 'Bearer ' . $token);
        }

        return $next($request);
    }
}
