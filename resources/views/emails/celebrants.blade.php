<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 20px; color: #334155; }
        .container { max-width: 650px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0; }
        .header { background-color: #1e40af; color: #ffffff; text-align: center; padding: 24px 20px; }
        .header h1 { margin: 0; font-size: 22px; }
        .content { padding: 30px 20px; }
        .table-container { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table-container th { background-color: #f1f5f9; text-align: left; padding: 12px; font-size: 14px; color: #475569; border-bottom: 2px solid #e2e8f0; }
        .table-container td { padding: 12px; font-size: 14px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
        .badge { background-color: #eff6ff; color: #1d4ed8; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .unit-text { color: #64748b; font-size: 13px; font-weight: bold; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #94a3b8; background-color: #f8fafc; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Aniversariantes do Dia - Núcleo Desenvolve</h1>
        </div>
        
        <div class="content">
            <p>Olá, equipe!</p>
            <p>Hoje (<strong>{{ \Carbon\Carbon::today()->format('d/m/Y') }}</strong>) estamos celebrando mais um ano de vida para os seguintes aniversariantes:</p>

            <table class="table-container">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Tipo</th>
                        <th>Unidade(s)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($celebrants as $celebrant)
                    <tr>
                        <td><strong>{{ $celebrant->name }}</strong></td>
                        <td><span class="badge">{{ $celebrant->tipo_pessoa }}</span></td>
                        <td>
                            <span class="unit-text">
                                @if($celebrant->tipo_pessoa === 'Profissional(is)')
                                    {{ $celebrant->units ? $celebrant->units->pluck('city')->join(', ') : 'N/A' }}
                                @else
                                    {{ $celebrant->unit->city ?? 'N/A' }}
                                @endif
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <p style="margin-top: 25px;">Por favor, não se esqueçam de enviar as felicitações! A Núcleo Desenvolve deseja um feliz aniversário a todos.</p>
        </div>

        <div class="footer">
            <p>Este é um e-mail automático gerado pela Plataforma ND.<br>Por favor, não responda diretamente a esta mensagem.</p>
        </div>
    </div>
</body>
</html>