<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Terapias Realizadas</title>
    <style>
        @page { margin: 30px; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10px; color: #333; }
        
        header { width: 100%; border-bottom: 2px solid #48D1CC; padding-bottom: 15px; margin-bottom: 20px; }
        .logo-container { float: left; width: 30%; }
        .logo { max-height: 55px; }
        .info-container { float: right; width: 70%; text-align: right; }
        .info-container h1 { margin: 0; font-size: 20px; color: #2C3E50; text-transform: uppercase; }
        .info-container p { margin: 4px 0 0 0; font-size: 11px; color: #7F8C8D; }
        .clear { clear: both; } 

        .summary-box { font-size: 14px; background-color: #F9FAFB; border: 1px solid #E5E7EB; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 11px; }
        .summary-box span { font-weight: bold; color: #48D1CC; margin-right: 1px; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #48D1CC; color: white; padding: 10px 8px; text-align: left; font-size: 10px; text-transform: uppercase; }
        td { border-bottom: 1px solid #E5E7EB; padding: 10px 8px; color: #4B5563; font-size: 9px; }
        tr:nth-child(even) { background-color: #F9FAFB; }
        
        footer { position: fixed; bottom: -10px; left: 0px; right: 0px; height: 20px; font-size: 9px; text-align: center; color: #9CA3AF; border-top: 1px solid #E5E7EB; padding-top: 8px; }
    </style>
</head>
<body>

    <header>
        <div class="logo-container">
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/icon-nd.png'))) }}" class="logo" alt="Logo Clínica">
        </div>
        <div class="info-container">
            <h1>Relatório de Terapias Realizadas</h1>
            <p>Gerado em: {{ now()->timezone('America/Fortaleza')->format('d/m/Y \à\s H:i') }}</p>
            <p>Emitido por: {{ auth()->user()->name ?? 'Sistema' }}</p>
        </div>
        <div class="clear"></div>
    </header>

    <div class="summary-box">
        <span>Total de Consultas:</span> {{ $totalConsultas }} <br>
        <span>Total de Sessões:</span> {{ $totalSessoes }}
    </div>

    <main>
        <table>
            <thead>
                <tr>
                    @if($selectedColumns['nome']) <th>Paciente</th> @endif
                    @if($selectedColumns['data']) <th>Data</th> @endif
                    @if($selectedColumns['guia']) <th>Guia</th> @endif
                    @if($selectedColumns['terapia']) <th>Terapia</th> @endif
                    @if($selectedColumns['tipo_atendimento']) <th>Atendimento</th> @endif
                    @if($selectedColumns['check_in']) <th>Check-in</th> @endif
                    @if($selectedColumns['check_out']) <th>Check-out</th> @endif
                    @if($selectedColumns['qtd_sessoes']) <th>Sessões</th> @endif
                    @if($selectedColumns['profissional']) <th>Profissional</th> @endif
                    @if($selectedColumns['registrado_em']) <th>Registrado</th> @endif
                    @if($selectedColumns['atualizado_em']) <th>Atualizado</th> @endif
                </tr>
            </thead>
            <tbody>
                @forelse($appointments as $item)
                    <tr>
                        @if($selectedColumns['nome']) <td>{{ $item->patient->name ?? 'N/A' }}</td> @endif
                        @if($selectedColumns['data']) <td>{{ $item->appointment_date ? \Carbon\Carbon::parse($item->appointment_date)->format('d/m/Y') : '-' }}</td> @endif
                        @if($selectedColumns['guia']) <td>{{ $item->guide ?? '-' }}</td> @endif
                        @if($selectedColumns['terapia']) <td class="uppercase-text">{{ $item->therapy?->name }}</td> @endif
                        @if($selectedColumns['tipo_atendimento']) <td>{{ $item->serviceType->name ?? 'N/A' }}</td> @endif
                        @if($selectedColumns['check_in']) <td>{{ $item->check_in ? \Carbon\Carbon::parse($item->check_in)->format('H:i') : '-' }}</td> @endif
                        @if($selectedColumns['check_out']) <td>{{ $item->check_out ? \Carbon\Carbon::parse($item->check_out)->format('H:i') : '-' }}</td> @endif
                        @if($selectedColumns['qtd_sessoes']) <td>{{ $item->session_number }}</td> @endif
                        @if($selectedColumns['profissional']) <td>{{ $item->professional->name ?? 'N/A' }}</td> @endif
                        @if($selectedColumns['registrado_em']) <td>{{ $item->created_at ? $item->created_at->format('d/m/Y H:i') : '-' }}</td> @endif
                        @if($selectedColumns['atualizado_em']) <td>{{ $item->updated_at ? $item->updated_at->format('d/m/Y H:i') : '-' }}</td> @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" style="text-align: center; padding: 20px;">Nenhum registo encontrado para os filtros aplicados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </main>

    <footer>
        Documento gerado automaticamente pelo sistema de gestão clínica.
    </footer>

</body>
</html>