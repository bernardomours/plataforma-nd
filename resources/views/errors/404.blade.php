@extends('errors.layout')

@section('codigo', '404')
@section('titulo', 'Página não encontrada')

@section('icone')
    <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
    </svg>
@endsection

@section('mensagem')
    O endereço acessado não existe ou o registro procurado não está mais disponível.
@endsection

{{--
    Aviso importante para este sistema: com o isolamento por unidade (trait
    IsolatesByUnit), um paciente de OUTRA clínica simplesmente não é encontrado pela
    consulta — e o route model binding responde 404, não 403. Sem esta explicação, o
    usuário acha que o registro foi apagado quando na verdade ele existe em outra unidade.
--}}
@section('detalhe')
    <strong>Se você esperava encontrar um registro aqui</strong>
    Ele pode pertencer a outra unidade, ter sido movido para o histórico de saída,
    ou ter sido excluído. Confirme com a coordenação da sua unidade.
@endsection
