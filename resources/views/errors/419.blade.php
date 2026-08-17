@extends('errors.layout')

@section('codigo', '419')
@section('titulo', 'Sessão expirada')
@section('cor', 'ambar')

@section('icone')
    <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
@endsection

@section('mensagem')
    A página ficou aberta tempo demais e a sessão de segurança expirou.
    Nada do que você fez antes foi perdido no sistema — apenas esta tela precisa ser recarregada.
@endsection

{{--
    O 419 é o erro mais frequente num sistema de clínica: o computador da recepção fica
    com a tela aberta o dia inteiro e o token CSRF vence. Vale explicar em vez de mostrar
    o críptico "Page Expired" padrão do Laravel.
--}}
@section('detalhe')
    <strong>O que fazer</strong>
    Recarregue a página e entre novamente. Se estava preenchendo um formulário longo,
    copie o que digitou antes de recarregar.
@endsection

@section('acoes')
    <button type="button" onclick="location.reload()" class="btn btn-primario">Recarregar página</button>
    <a href="{{ url('/login') }}" class="btn btn-secundario">Ir para o login</a>
@endsection
