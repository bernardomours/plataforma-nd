@extends('errors.layout')

@section('codigo', '503')
@section('titulo', 'Sistema em manutenção')

@section('icone')
    <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
    </svg>
@endsection

@section('mensagem')
    Estamos aplicando uma atualização no sistema. O acesso volta em alguns minutos —
    nenhum dado é perdido durante a manutenção.
@endsection

{{--
    Esta é a tela do `php artisan down`, usada durante o deploy. Não há botão de "voltar":
    recarregar é a única ação útil enquanto a manutenção não termina.
--}}
@section('acoes')
    <button type="button" onclick="location.reload()" class="btn btn-primario">Tentar novamente</button>
@endsection
