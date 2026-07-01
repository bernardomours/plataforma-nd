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
        if (!in_array(auth()->user()->role, ['admin', 'manager'])) {
                abort(403, 'Acesso não autorizado.');
            }        $this->mes = now()->month;
            
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
            
            $graficoTerapiasSemana = [];
            foreach($seriesTerapias as $nome => $valores) {
                $graficoTerapiasSemana[] = ['name' => $nome, 'data' => array_values($valores)];
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

        return view('livewire.relatorios.geral', array_merge([
            'convenios' => Agreement::orderBy('name')->get(),
            'pacientes' => Patient::select('id', 'name')->orderBy('name')->get(),
            'terapias' => Therapy::orderBy('name')->get(),
            'unidades' => Unit::all(),
        ], $this->viewMode === 'geral' ? $dadosGerais : $dadosComparativos));
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