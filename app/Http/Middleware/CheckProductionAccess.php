<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckProductionAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->guest(route('login'));
        }

        if (!auth()->user()->can_access_production) {
            return redirect()->route('dashboard')->with('error', 'Você não tem permissão para acessar a Área de Produção.');
        }

        return $next($request);
    }
}