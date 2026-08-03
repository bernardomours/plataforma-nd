<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Extrato de Produção</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #ddd; }
        .header h1 { margin: 0; font-size: 18px; }
        table { w-full: 100%; width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; }
        .text-right { text-align: right; }
        
        /* Estilos para a Glosa */
        .row-glosado { background-color: #fee2e2; color: #991b1b; }
        .badge-glosa { background-color: #ef4444; color: white; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: bold; }
        
        .resumo-box { margin-top: 30px; border: 1px solid #ddd; padding: 15px; background-color: #f9fafb; width: 300px; float: right; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Extrato de Produção Clínica</h1>
        <p><strong>Profissional:</strong> {{ $profissional->name }}</p>
        <p><strong>Período:</strong> {{ str_pad($mes, 2, '0', STR_PAD_LEFT) }} / {{ $ano }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Paciente</th>
                <th>Terapia</th>
                <th>Ambiente</th>
                <th>Sessões</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($atendimentos as $atendimento)
                <!-- Se for glosado, pinta a linha de vermelho claro -->
                <tr class="{{ $atendimento->is_glosado ? 'row-glosado' : '' }}">
                    <td>{{ \Carbon\Carbon::parse($atendimento->appointment_date)->format('d/m/Y') }}</td>
                    <td>{{ $atendimento->patient->name ?? 'N/A' }}</td>
                    <td>{{ $atendimento->therapy->name ?? 'N/A' }}</td>
                    <td>{{ $atendimento->serviceType->name ?? 'Clínica' }}</td>
                    <td style="text-align: center">{{ $atendimento->session_number ?? 1 }}</td>
                    <td>
                        @if($atendimento->is_glosado)
                            <span class="badge-glosa">GLOSADO</span>
                        @else
                            Pago
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center;">Nenhum atendimento registrado neste período.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="resumo-box">
        <h3 style="margin-top: 0;">Resumo do Repasse</h3>
        <p><strong>Sessões Válidas:</strong> {{ $resumo['sessoes'] }}</p>
        <!-- <p><strong>Regras Aplicadas:</strong> <br> <span style="font-size: 10px;">{{ $resumo['valor_regra'] }}</span></p> -->
        <hr style="border: 0; border-top: 1px solid #ccc;">
        <h2 style="margin-bottom: 0; color: #166534;">Total a Receber: R$ {{ number_format($resumo['valor_total'], 2, ',', '.') }}</h2>
    </div>

</body>
</html>