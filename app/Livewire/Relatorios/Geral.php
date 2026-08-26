<?php

namespace App\Livewire\Relatorios;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Therapy;
use App\Models\Unit;
use App\Models\Agreement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

#[Layout('layouts.app')]
class Geral extends Component
{
    use WithPagination;

    public $mes;
    public $ano;
    public $convenio_id = '';
    public $paciente_id = '';
    public $terapia_id = '';
    public $unidade_id = '';
    public $search = '';
    public $viewMode = 'geral';

    public $anosDisponiveis = [];

    public function mount()
    {
        // Papel pelo Spatie, não pela coluna users.role (legado, congelada desde 04/2026).
        // A checagem aqui não é redundante com o middleware da rota: as ações do Livewire
        // vão para livewire/update, que não reexecuta o middleware da rota original.
        if (! auth()->user()->hasAnyRole(['admin', 'manager'])) {
            abort(403, 'Acesso não autorizado.');
        }

        $this->mes = now()->month;
        $this->ano = now()->year;

        for ($i = 0; $i <= 5; $i++) {
            $ano = now()->subYears($i)->year;
            $this->anosDisponiveis[$ano] = $ano;
        }
    }

    public function aplicarFiltros()
    {
        $this->resetPage();
    }

    public function limparFiltros()
    {
        $this->reset(['convenio_id', 'paciente_id', 'terapia_id', 'unidade_id', 'search']);
        $this->mes = now()->month;
        $this->ano = now()->year;
        $this->resetPage();
    }

    public function updatedSearch() { $this->resetPage(); }

    private function getBaseQuery()
    {
        return Appointment::query()
            ->whereYear('appointment_date', $this->ano)
            ->whereMonth('appointment_date', $this->mes)
            ->when($this->terapia_id, fn($q) => $q->where('therapy_id', $this->terapia_id))
            ->when($this->paciente_id, fn($q) => $q->where('patient_id', $this->paciente_id))
            ->when($this->unidade_id, function ($q) {
                $q->whereHas('patient', fn($p) => $p->where('unit_id', $this->unidade_id));
            })
            ->when($this->convenio_id, function ($q) {
                $q->whereHas('patient', fn($p) => $p->where('agreement_id', $this->convenio_id));
            });
    }

    public function render()
    {
        $queryBase = $this->getBaseQuery();
        $dadosGerais = [];
        $dadosComparativos = [];

        if ($this->viewMode === 'geral') {

            $dadosGerais['totalSessoes'] = (clone $queryBase)->sum('session_number');
            $dadosGerais['totalAtendimentos'] = (clone $queryBase)->count();
            $dadosGerais['beneficiariosAtendidos'] = (clone $queryBase)->distinct('patient_id')->count('patient_id');
            
            $diasComAtendimento = (clone $queryBase)->distinct(DB::raw('DATE(appointment_date)'))->count(DB::raw('DATE(appointment_date)'));
            $dadosGerais['mediaDiaria'] = $diasComAtendimento > 0 ? round($dadosGerais['totalSessoes'] / $diasComAtendimento, 0) : 0;

            $dadosGerais['graficoDias'] = (clone $queryBase)->selectRaw('DATE(appointment_date) as data, SUM(session_number) as total')->groupBy('data')->orderBy('data')->get();
            // A fita do mês precisa do mês inteiro, não só dos dias com movimento: é o vazio
            // que mostra feriado, fim de semana e queda de operação.
            $porDia = $dadosGerais['graficoDias']->keyBy(fn ($d) => (string) $d->data);
            $cursor = Carbon::create($this->ano, $this->mes, 1);
            $fita = [];

            while ($cursor->month == $this->mes) {
                $chave = $cursor->toDateString();

                $fita[] = [
                    'dia'      => $cursor->day,
                    'inicial'  => mb_strtoupper(mb_substr($cursor->translatedFormat('D'), 0, 1)),
                    'fimDeSemana' => $cursor->isWeekend(),
                    'total'    => (int) ($porDia[$chave]->total ?? 0),
                    'titulo'   => $cursor->translatedFormat('D, d/m'),
                ];

                $cursor->addDay();
            }

            $dadosGerais['fitaDoMes'] = $fita;
            $dadosGerais['picoDoMes'] = max(1, max(array_column($fita, 'total')));

            $dadosGerais['graficoTerapias'] = (clone $queryBase)->join('therapies', 'appointments.therapy_id', '=', 'therapies.id')->selectRaw('therapies.name as nome, SUM(appointments.session_number) as total')->groupBy('therapies.id', 'therapies.name')->orderByDesc('total')->get();
            $dadosGerais['graficoConvenios'] = (clone $queryBase)->join('patients', 'appointments.patient_id', '=', 'patients.id')->join('agreements', 'patients.agreement_id', '=', 'agreements.id')->selectRaw('agreements.name as nome, SUM(appointments.session_number) as total')->groupBy('agreements.id', 'agreements.name')->orderByDesc('total')->get();
            $dadosGerais['graficoUnidades'] = (clone $queryBase)->join('patients', 'appointments.patient_id', '=', 'patients.id')->leftJoin('units', 'patients.unit_id', '=', 'units.id')->selectRaw('COALESCE(units.city, units.name, "Sem Unidade") as nome, SUM(appointments.session_number) as total')->groupBy('nome')->orderByDesc('total')->get();
            $dadosGerais['graficoBeneficiariosUnidade'] = (clone $queryBase)->join('patients', 'appointments.patient_id', '=', 'patients.id')->leftJoin('units', 'patients.unit_id', '=', 'units.id')->selectRaw('COALESCE(units.city, units.name, "Sem Unidade") as nome, COUNT(DISTINCT appointments.patient_id) as total')->groupBy('nome')->orderByDesc('total')->get();
            
            $tabelaQuery = clone $queryBase;
            if (!empty($this->search)) {
                $tabelaQuery->whereHas('patient', fn($q) => $q->where('name', 'like', '%' . $this->search . '%'));
            }
            $dadosGerais['tabelaResumo'] = $tabelaQuery->selectRaw('patient_id, therapy_id, SUM(session_number) as total_sessoes')->with(['patient', 'therapy'])->groupBy('patient_id', 'therapy_id')->paginate(15);

        } else {

            $dataAnterior = Carbon::create($this->ano, $this->mes, 1)->subMonth();
            
            $queryAnterior = Appointment::query()
                ->whereYear('appointment_date', $dataAnterior->year)
                ->whereMonth('appointment_date', $dataAnterior->month)
                ->when($this->terapia_id, fn($q) => $q->where('therapy_id', $this->terapia_id))
                ->when($this->paciente_id, fn($q) => $q->where('patient_id', $this->paciente_id))
                ->when($this->unidade_id, fn($q) => $q->whereHas('patient', fn($p) => $p->where('unit_id', $this->unidade_id)))
                ->when($this->convenio_id, fn($q) => $q->whereHas('patient', fn($p) => $p->where('agreement_id', $this->convenio_id)));

            $atuaisDiario = (clone $queryBase)
                ->selectRaw('DATE(appointment_date) as data, SUM(session_number) as total')
                ->groupBy('data')
                ->having('total', '>', 0)
                ->orderBy('data')
                ->get();

            $anterioresDiario = $queryAnterior
                ->selectRaw('DATE(appointment_date) as data, SUM(session_number) as total')
                ->groupBy('data')
                ->having('total', '>', 0)
                ->orderBy('data')
                ->get();

            $mapaDias = [0 => 'Domingo', 1 => 'Segunda-feira', 2 => 'Terça-feira', 3 => 'Quarta-feira', 4 => 'Quinta-feira', 5 => 'Sexta-feira', 6 => 'Sábado'];
            
            $somaDiasAtuais = [];
            foreach($atuaisDiario as $d) {
                $wd = Carbon::parse($d->data)->dayOfWeek;
                $somaDiasAtuais[$wd] = ($somaDiasAtuais[$wd] ?? 0) + $d->total;
            }
            arsort($somaDiasAtuais);
            $melhorDiaAtualKey = array_key_first($somaDiasAtuais);
            
            $somaDiasAnteriores = [];
            foreach($anterioresDiario as $d) {
                $wd = Carbon::parse($d->data)->dayOfWeek;
                $somaDiasAnteriores[$wd] = ($somaDiasAnteriores[$wd] ?? 0) + $d->total;
            }
            arsort($somaDiasAnteriores);
            $melhorDiaAnteriorKey = array_key_first($somaDiasAnteriores);

            $acumuladoAtual = [];
            $acumuladoAnterior = [];
            $somaAtual = 0;
            $somaAnterior = 0;
            
            foreach ($atuaisDiario as $d) {
                $somaAtual += $d->total;
                $acumuladoAtual[] = $somaAtual;
            }

            foreach ($anterioresDiario as $d) {
                $somaAnterior += $d->total;
                $acumuladoAnterior[] = $somaAnterior;
            }

            $maxDiasTrabalhados = max(count($acumuladoAtual), count($acumuladoAnterior));
            $diasLabels = [];
            for($i = 1; $i <= $maxDiasTrabalhados; $i++) {
                $diasLabels[] = $i . 'º Dia';
            }

            $terapiasRaw = (clone $queryBase)->selectRaw('DATE(appointment_date) as data, therapy_id, SUM(session_number) as total')->with('therapy')->groupBy('data', 'therapy_id')->get();
            $seriesTerapias = [];
            
            foreach($terapiasRaw as $row) {
                $nome = $row->therapy->name ?? 'Outros';
                $wd = Carbon::parse($row->data)->dayOfWeek;
                if ($wd >= 1 && $wd <= 6) {
                    if (!isset($seriesTerapias[$nome])) $seriesTerapias[$nome] = [1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0];
                    $seriesTerapias[$nome][$wd] += $row->total;
                }
            }
            
            // Teto de 7 séries + "Outras": a paleta categórica tem 8 posições e são 12
            // terapias. Sem o teto o ApexCharts recicla cor, e duas terapias viram a mesma.
            uasort($seriesTerapias, fn ($a, $b) => array_sum($b) <=> array_sum($a));

            $graficoTerapiasSemana = [];
            $outras = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];

            foreach ($seriesTerapias as $nome => $valores) {
                if (count($graficoTerapiasSemana) < 7) {
                    $graficoTerapiasSemana[] = ['name' => $nome, 'data' => array_values($valores)];
                    continue;
                }

                foreach ($valores as $dia => $v) {
                    $outras[$dia] += $v;
                }
            }

            if (array_sum($outras) > 0) {
                $graficoTerapiasSemana[] = ['name' => 'Outras', 'data' => array_values($outras)];
            }

            $media = count($atuaisDiario) > 0 ? round($atuaisDiario->sum('total') / count($atuaisDiario), 0) : 0;

            $dadosComparativos = [
                'melhorDiaAtual' => $melhorDiaAtualKey !== null ? $mapaDias[$melhorDiaAtualKey] : '-',
                'totalMelhorDiaAtual' => $somaDiasAtuais[$melhorDiaAtualKey] ?? 0,
                'melhorDiaAnterior' => $melhorDiaAnteriorKey !== null ? $mapaDias[$melhorDiaAnteriorKey] : '-',
                'totalMelhorDiaAnterior' => $somaDiasAnteriores[$melhorDiaAnteriorKey] ?? 0,
                'mediaDiaria' => $media,
                'linhaAtual' => $acumuladoAtual,
                'linhaAnterior' => $acumuladoAnterior, 
                'diasLabels' => $diasLabels,
                'graficoTerapiasSemana' => $graficoTerapiasSemana
            ];
        }

        $dadosOcupacao = $this->viewMode === 'ocupacao' ? $this->dadosDeOcupacao() : [];

        return view('livewire.relatorios.geral', array_merge([
            'convenios' => Agreement::orderBy('name')->get(),
            'pacientes' => Patient::select('id', 'name')->orderBy('name')->get(),
            'terapias' => Therapy::orderBy('name')->get(),
            'unidades' => Unit::all(),
        ], match ($this->viewMode) {
            'geral'      => $dadosGerais,
            'ocupacao'   => $dadosOcupacao,
            default      => $dadosComparativos,
        }));
    }

    /**
     * Frequência e ocupação: quando a clínica enche.
     *
     * Uma consulta agrega por dia da semana e hora do check-in; os quatro painéis são
     * recortes dela em PHP. Assim todos falam do mesmo número — painel montado com
     * consultas independentes tende a divergir sozinho.
     *
     * Soma SESSÕES (`session_number`), como todo quantitativo do sistema. Um atendimento
     * pode valer 2 ou 4 sessões conforme a duração e o convênio, e é a sessão que conta
     * para faturamento e para a CH — contar atendimento subestimaria Natal e Mossoró, onde
     * a média é 1,75 e 2,03 por atendimento, contra 1,00 em João Câmara e Santa Cruz.
     */
    private function dadosDeOcupacao(): array
    {
        $linhas = $this->getBaseQuery()
            ->whereNotNull('check_in')
            ->selectRaw('DAYOFWEEK(appointment_date) dia_semana, HOUR(check_in) hora,
                         SUM(session_number) total, COUNT(DISTINCT DATE(appointment_date)) dias')
            ->groupBy('dia_semana', 'hora')
            ->get();

        $total = (int) $linhas->sum('total');

        $nomesDias = [2 => 'Segunda', 3 => 'Terça', 4 => 'Quarta', 5 => 'Quinta',
                      6 => 'Sexta', 7 => 'Sábado', 1 => 'Domingo'];

        // Só entram dias e horas com movimento: a clínica não abre fim de semana, e
        // reservar coluna para domingo vazio só encolhe as que importam.
        $diasAtivos = [];
        foreach ($nomesDias as $numero => $nome) {
            if ($linhas->where('dia_semana', $numero)->sum('total') > 0) {
                $diasAtivos[$numero] = $nome;
            }
        }

        $horasAtivas = $linhas->pluck('hora')->unique()->sort()->values()->all();

        $porDia = [];
        foreach ($diasAtivos as $numero => $nome) {
            $n = (int) $linhas->where('dia_semana', $numero)->sum('total');
            $manha = (int) $linhas->where('dia_semana', $numero)->where('hora', '<', 12)->sum('total');

            $porDia[] = [
                'dia'        => $nome,
                'total'      => $n,
                'percentual' => $total > 0 ? round($n / $total * 100, 2) : 0,
                'manha'      => $manha,
                'tarde'      => $n - $manha,
                'pct_manha'  => $n > 0 ? round($manha / $n * 100, 1) : 0,
                'pct_tarde'  => $n > 0 ? round(($n - $manha) / $n * 100, 1) : 0,
            ];
        }

        $porHora = [];
        foreach ($horasAtivas as $hora) {
            $n = (int) $linhas->where('hora', $hora)->sum('total');

            $porHora[] = [
                'rotulo'     => sprintf('%02dh', $hora),
                'total'      => $n,
                'percentual' => $total > 0 ? round($n / $total * 100, 2) : 0,
            ];
        }

        // Heatmap do ApexCharts: uma série por dia, um ponto por hora.
        $mapaCalor = [];
        foreach (array_reverse($diasAtivos, true) as $numero => $nome) {
            $pontos = [];

            foreach ($horasAtivas as $hora) {
                $pontos[] = [
                    'x' => sprintf('%02dh', $hora),
                    'y' => (int) $linhas->where('dia_semana', $numero)->where('hora', $hora)->sum('total'),
                ];
            }

            $mapaCalor[] = ['name' => $nome, 'data' => $pontos];
        }

        $diasUteis = (int) $this->getBaseQuery()
            ->distinct()->count(DB::raw('DATE(appointment_date)'));

        $manhaTotal = (int) $linhas->where('hora', '<', 12)->sum('total');
        $picoDia    = collect($porDia)->sortByDesc('total')->first();
        $picoHora   = collect($porHora)->sortByDesc('total')->first();

        return [
            'ocupTotal'      => $total,
            'ocupDiasUteis'  => $diasUteis,
            'ocupMedia'      => $diasUteis > 0 ? round($total / $diasUteis) : 0,
            'ocupPicoDia'    => $picoDia,
            'ocupPicoHora'   => $picoHora,
            'ocupPctManha'   => $total > 0 ? round($manhaTotal / $total * 100, 1) : 0,
            'ocupPctTarde'   => $total > 0 ? round(($total - $manhaTotal) / $total * 100, 1) : 0,
            'ocupPorDia'     => $porDia,
            'ocupPorHora'    => $porHora,
            'ocupMapaCalor'  => $mapaCalor,
        ];
    }

    public function exportarPDF()
    {
        // Usa a mesma base de dados filtrada da tela
        $query = $this->getBaseQuery();
        
        $totalSessoes = (clone $query)->sum('session_number');
        $totalPacientesUnicos = (clone $query)->distinct('patient_id')->count('patient_id');
        
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        
        // Evolução diária
        $evolucaoDiaria = (clone $query)->select(
            $isSqlite 
                ? DB::raw("strftime('%d', appointment_date) as dia")
                : DB::raw("DATE_FORMAT(appointment_date, '%d') as dia"),
            DB::raw('SUM(session_number) as total')
        )
        ->groupBy('dia')
        ->pluck('total', 'dia');

        $diasComAtendimento = $evolucaoDiaria->count(); 
        $mediaDiaria = ($diasComAtendimento > 0) ? round($totalSessoes / $diasComAtendimento) : 0;

        $sessoesPorTerapia = (clone $query)
        ->join('therapies', 'appointments.therapy_id', '=', 'therapies.id')
        ->selectRaw('therapies.name, SUM(appointments.session_number) as total')
        ->groupBy('therapies.name')
        ->pluck('total', 'therapies.name');

        $sessoesPorConvenio = (clone $query)
            ->join('patients', 'appointments.patient_id', '=', 'patients.id')
            ->join('agreements', 'patients.agreement_id', '=', 'agreements.id')
            ->selectRaw('agreements.name, SUM(appointments.session_number) as total')
            ->groupBy('agreements.name')
            ->pluck('total', 'agreements.name');

        $sessoesPorUnidade = (clone $query)
            ->join('patients', 'appointments.patient_id', '=', 'patients.id')
            ->leftJoin('units', 'patients.unit_id', '=', 'units.id')
            ->selectRaw('COALESCE(units.city, units.name, "Sem Unidade") as nome, SUM(appointments.session_number) as total')
            ->groupBy('nome')
            ->pluck('total', 'nome');

        $resumo = (clone $query)
            ->join('patients', 'appointments.patient_id', '=', 'patients.id')
            ->join('therapies', 'appointments.therapy_id', '=', 'therapies.id')
            ->select(
                $isSqlite 
                    ? DB::raw("strftime('%m/%Y', appointments.appointment_date) as reference_month")
                    : DB::raw("DATE_FORMAT(appointments.appointment_date, '%m/%Y') as reference_month"),
                'patients.name as patient_name',
                'therapies.name as therapy_name',
                DB::raw('SUM(appointments.session_number) as total_sessions')
            )
            ->groupBy('reference_month', 'patients.id', 'therapies.id', 'patients.name', 'therapies.name')
            ->orderBy('patients.name', 'asc')
            ->get();

        $nomesUnidades = $this->unidade_id ? \App\Models\Unit::find($this->unidade_id)?->city : 'Todas as Unidades';

        // Gera o PDF
        $pdf = Pdf::loadView('pdf.relatorio-geral', [
            'mesSelecionado' => str_pad($this->mes, 2, '0', STR_PAD_LEFT),
            'anoSelecionado' => $this->ano,
            'nomesUnidades' => $nomesUnidades,
            'resumo' => $resumo,
            'totalSessoes' => $totalSessoes,
            'mediaDiaria' => $mediaDiaria,
            'totalPacientesUnicos' => $totalPacientesUnicos,
            'sessoesPorTerapia' => $sessoesPorTerapia,
            'sessoesPorConvenio' => $sessoesPorConvenio,
            'sessoesPorUnidade' => $sessoesPorUnidade,
            'evolucaoDiaria' => $evolucaoDiaria,
        ]);

        // Retorna o download direto na tela
        return response()->streamDownload(fn () => print($pdf->output()), "relatorio-atendimentos-{$this->mes}-{$this->ano}.pdf");
    }
}