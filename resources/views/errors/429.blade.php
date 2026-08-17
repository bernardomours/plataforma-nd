@extends('errors.layout')

@section('codigo', '429')
@section('titulo', 'Muitas tentativas')
@section('cor', 'ambar')

@section('icone')
    <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
    </svg>
@endsection

@section('mensagem')
    Foram feitas muitas tentativas em pouco tempo e o acesso foi temporariamente bloqueado.
    Aguarde alguns minutos antes de tentar de novo.
@endsection

{{--
    O LoginForm limita a 5 tentativas por e-mail + IP (RateLimiter). É a origem mais
    provável deste erro: alguém errando a senha repetidamente.
--}}
@section('detalhe')
    <strong>Esqueceu a senha?</strong>
    Em vez de tentar novamente, use a opção "Esqueceu a senha?" na tela de login,
    ou peça ao administrador para redefinir o seu acesso.
@endsection
