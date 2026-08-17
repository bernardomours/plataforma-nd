<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Prende o usuário na tela de troca de senha enquanto ele estiver com a senha padrão.
 *
 * Só age quando users.must_change_password está marcado — para todo o resto do sistema
 * é um no-op, sem consulta extra ao banco (o usuário autenticado já está carregado).
 */
class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->must_change_password) {
            return $next($request);
        }

        // As requisições do Livewire precisam passar. A própria tela de troca é um
        // componente Livewire, e o logout do Breeze também é uma ação Livewire — bloquear
        // essas chamadas deixaria o usuário preso, sem conseguir trocar a senha nem sair.
        if ($request->is('livewire/*')) {
            return $next($request);
        }

        // Rotas que precisam continuar acessíveis para não criar laço de redirecionamento.
        // (não há rota nomeada 'logout' — o logout do Breeze é ação Livewire, já liberada
        // pela condição acima.)
        if ($request->routeIs('password.change', 'producao.sair')) {
            return $next($request);
        }

        return redirect()->route('password.change');
    }
}
