@extends('errors.layout')

@section('codigo', '403')
@section('titulo', 'Acesso não autorizado')
@section('cor', 'ambar')

@section('icone')
    <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
    </svg>
@endsection

@section('mensagem')
    Você não tem permissão para acessar esta página ou executar esta ação.
@endsection

{{--
    As 17 chamadas de abort(403) no sistema trazem mensagens específicas ("Você não tem
    permissão para excluir pacientes definitivamente", "Paciente fora das unidades
    permitidas", etc.). Exibimos essa mensagem quando existir — é ela que diz ao usuário
    o que exatamente foi barrado, em vez de um "acesso negado" genérico.
--}}
@if (isset($exception) && filled($exception->getMessage()))
    @section('detalhe')
        <strong>Detalhe</strong>
        {{ $exception->getMessage() }}
    @endsection
@endif
