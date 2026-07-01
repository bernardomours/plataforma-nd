<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Auditoria Humana Saúde - Núcleo Desenvolve</title>
    <style>
        @page { margin: 30px; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; color: #333; }
        header { border-bottom: 2px solid #48D1CC; padding-bottom: 15px; margin-bottom: 15px; }
        .logo-container { float: left; width: 30%; }
        .info-container { float: right; width: 70%; text-align: right; }
        .info-container h1 { margin: 0; font-size: 18px; color: #2C3E50; text-transform: uppercase; }
        .info-container p { margin: 4px 0 0 0; font-size: 11px; color: #7F8C8D; }
        .clear { clear: both; }
        
        .widgets-table { width: 100%; margin-bottom: 15px; border-spacing: 6px; border-collapse: separate; }
        .widget-card { background-color: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 8px; padding: 10px; vertical-align: top; width: 25%; }
        .widget-title { font-size: 9px; color: #6B7280; text-transform: uppercase; margin-bottom: 6px; display: block; }
        .widget-value { font-size: 18px; font-weight: bold; color: #111827; }

        table.data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .data-table th { background-color: #48D1CC; color: white; padding: 6px; text-align: left; text-transform: uppercase; font-size: 9px; }
        .data-table td { border-bottom: 1px solid #E5E7EB; padding: 6px; font-size: 9px; }
        .data-table tr:nth-child(even) { background-color: #F9FAFB; }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        
        /* Cores do Status baseadas no Livewire */
        .status-green { color: #059669; font-weight: bold; }
        .status-red { color: #DC2626; font-weight: bold; }
        .status-yellow { color: #D97706; font-weight: bold; }
    </style>
</head>
<body>

    <header>
        <div class="logo-container">
            <!-- A mesma logo que você já usa -->
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/icon-nd.png'))) }}" style="max-height: 40px;" alt="Logo ND">
        </div>
        <div class="info-container">
            <h1>Auditoria: Humana Saúde</h1>
            <p>Emitido em: {{ now()->timezone('America/Fortaleza')->format('d/m/Y H:i') }}</p>
            <p><strong>Competência:</strong> {{ str_pad($mes, 2, '0', STR_PAD_LEFT) }}/{{ $ano }}</p>
            <p><strong>Unidade Filtrada:</strong> {{ $unidadeNome }}</p>
        </div>
        <div class="clear"></div>
    </header>

    <main>
        <!-- Cards Superiores com o Resumo -->
        <table class="widgets-table">
            <tr>
                <td class="widget-card">
                    <span class="widget-title">Total Sessões (Sistema)</span>
                    <span class="widget-value">{{ $totalSistema }}</span>
                </td>
                <td class="widget-card">
                    <span class="widget-title">Total Sessões (Humana)</span>
                    <span class="widget-value">{{ $totalHumana }}</span>
                </td>
                <td class="widget-card">
                    <span class="widget-title">Registros Corretos</span>
                    <span class="widget-value status-green">{{ $totalBateu }}</span>
                </td>
                <td class="widget-card">
                    <span class="widget-title">Total de Divergências</span>
                    <span class="widget-value status-red">{{ $totalDivergencias }}</span>
                </td>
            </tr>
        </table>

        <!-- Tabela Principal do Relatório -->
        <table class="data-table">
            <thead>
                <tr>
                    <th width="40%">Paciente</th>
                    <th width="20%">Terapia</th>
                    <th class="text-center" width="10%">Qtd. Sistema</th>
                    <th class="text-center" width="10%">Qtd. Humana</th>
                    <th class="text-right" width="20%">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($resultados as $item)
                    <tr>
                        <td>{{ $item['paciente'] }}</td>
                        <td>{{ $item['terapia'] }}</td>
                        <td class="text-center"><strong>{{ $item['qtd_sistema'] }}</strong></td>
                        <td class="text-center"><strong>{{ $item['qtd_humana'] }}</strong></td>
                        <td class="text-right">
                            @if($item['cor'] === 'green')
                                <span class="status-green">{{ $item['status'] }}</span>
                            @elseif($item['cor'] === 'red')
                                <span class="status-red">{{ $item['status'] }}</span>
                            @else
                                <span class="status-yellow">{{ $item['status'] }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </main>

</body>
</html>