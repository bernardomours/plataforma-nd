@extends('errors.layout')

@section('codigo', '500')
@section('titulo', 'Erro interno do sistema')
@section('cor', 'vermelho')

@section('icone')
    <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
    </svg>
@endsection

@section('mensagem')
    Algo deu errado ao processar a sua solicitação. A falha foi registrada e a equipe
    técnica consegue verificar o que aconteceu.
@endsection

{{--
    NUNCA exibir $exception->getMessage() aqui: a mensagem de uma exceção não tratada
    pode conter caminho de arquivo, trecho de SQL ou nome de coluna. Numa tela pública
    isso é vazamento de informação sobre a estrutura do sistema.
--}}
@section('detalhe')
    <strong>Se o problema continuar</strong>
    Anote o que estava fazendo e o horário aproximado, e avise o suporte —
    isso é o que permite localizar o registro da falha.
@endsection
