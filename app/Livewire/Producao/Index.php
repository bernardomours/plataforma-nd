<?php

namespace App\Livewire\Producao;

use App\Models\Appointment;
use App\Models\GlosaBatch;
use App\Services\ProfessionalPayrollCalculator;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.producao')]
class Index extends Component
{
    /** Competência no formato `ano-mes`; o select da tela grava aqui. */
    public string $competencia = '';

    public int $mes;
    public int $ano;

    /** Quantos meses o gráfico de evolução mostra. */
    private const MESES_NO_HISTORICO = 6;

    public function mount(): void
    {
        $this->competencia = now()->year . '-' . now()->month;
        $this->aplicarCompetencia();
    }

    public function competencias(): array
    {
        $opcoes = [];

        for ($i = 0; $i < 12; $i++) {
            $data = now()->startOfMonth()->subMonths($i);
            $opcoes[$data->year . '-' . $data->month] = ucfirst($data->translatedFormat('F / Y'));
        }

        return $opcoes;
    }

    public function updatedCompetencia(): void
    {
        $this->aplicarCompetencia();
    }

    /** Sem confiar no valor recebido: fora das opções válidas, volta para o mês corrente. */
    private function aplicarCompetencia(): void
    {
        if (! array_key_exists($this->competencia, $this->competencias())) {
            $this->competencia = now()->year . '-' . now()->month;
        }

        [$this->ano, $this->mes] = array_map('intval', explode('-', $this->competencia));
    }

    /** Sessões por mês, sem aplicar regra de pagamento — só volume. */
    private function historico(): array
    {
        $inicio = Carbon::create($this->ano, $this->mes, 1)
            ->subMonths(self::MESES_NO_HISTORICO - 1)
            ->startOfMonth();

        $fim = Carbon::create($this->ano, $this->mes, 1)->endOfMonth();

        $porMes = Appointment::query()
            ->whereBetween('appointment_date', [$inicio, $fim])
            ->whereNotNull('check_in')
            ->where('is_glosado', false)
            ->selectRaw("CONCAT(YEAR(appointment_date), '-', MONTH(appointment_date)) as competencia, COALESCE(SUM(session_number), 0) as sessoes")
            ->groupBy('competencia')
            ->pluck('sessoes', 'competencia');

        $linha = [];

        for ($i = self::MESES_NO_HISTORICO - 1; $i >= 0; $i--) {
            $data = Carbon::create($this->ano, $this->mes, 1)->subMonths($i);

            $linha[] = [
                'rotulo'  => ucfirst($data->translatedFormat('M')),
                'titulo'  => ucfirst($data->translatedFormat('F / Y')),
                'sessoes' => (int) ($porMes[$data->year . '-' . $data->month] ?? 0),
                'atual'   => $i === 0,
            ];
        }

        return $linha;
    }

    public function render()
    {
        $calc = app(ProfessionalPayrollCalculator::class);

        $atual = $calc->totaisDoMes($this->ano, $this->mes);

        // Mês corrente ainda incompleto: comparar com o mês inteiro anterior daria uma queda falsa.
        $mesCorrente = $this->ano === (int) now()->year && $this->mes === (int) now()->month;
        $ateDia = $mesCorrente ? (int) now()->day : null;

        $mesAnterior = Carbon::create($this->ano, $this->mes, 1)->subMonth();
        $anterior = $calc->totaisDoMes($mesAnterior->year, $mesAnterior->month, $ateDia);

        $porProfissional = $atual['por_profissional'];

        $inicio = Carbon::create($this->ano, $this->mes, 1)->startOfDay();

        $semCheckOut = Appointment::query()
            ->where('appointment_date', '>=', $inicio)
            ->where('appointment_date', '<', (clone $inicio)->addMonth())
            ->whereNull('check_out')
            ->count();

        // O relatório do convênio chega ~2 meses depois, então quase nunca há glosa da
        // competência exibida. O painel mostra a última que existe, e diz qual é.
        $glosa = GlosaBatch::query()
            ->selectRaw('competencia, SUM(vl_apresentado) apresentado, SUM(vl_glosa) glosa')
            ->groupBy('competencia')
            ->orderByDesc('competencia')
            ->first();

        return view('livewire.producao.index', [
            'glosa' => $glosa,
            'totais'          => $atual,
            'anterior'        => $anterior,
            'semRegra'        => $porProfissional->where('situacao', 'sem_regra')->values(),
            'incompativeis'   => $porProfissional->where('situacao', 'incompativel')->values(),
            'inativos'        => $porProfissional->where('inativo', true)->values(),
            'ranking'         => $porProfissional->where('valor', '>', 0)->take(8),
            'regrasInvalidas' => $calc->regrasComTipoNaoSuportado(),
            'semCheckOut'     => $semCheckOut,
            'historico'       => $this->historico(),
            'competencias'    => $this->competencias(),
            'rotuloMes'       => ucfirst(Carbon::create($this->ano, $this->mes, 1)->translatedFormat('F / Y')),
            'parcial'         => $mesCorrente,
            'rotuloAnterior'  => ucfirst($mesAnterior->translatedFormat('F')),
        ]);
    }
}
