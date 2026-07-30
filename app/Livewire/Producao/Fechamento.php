<?php

namespace App\Livewire\Producao;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\Professional;
use App\Models\Appointment;
use App\Models\Therapy;
use App\Models\Unit;
use Carbon\Carbon;
use App\Models\ProfessionalPaymentRule;

#[Layout('layouts.producao')]
class Fechamento extends Component
{ 
    use WithPagination;

    public $mes;
    public $ano;
    public $profissional_id = '';
    public $terapia_id = '';
    public $unidade_id = '';

    public $modalExtratoAberto = false;
    public $profissionalExtratoNome = '';
    public $extratoAtendimentos = [];

    protected $cacheProducao = [];

    public function mount()
    {
        $this->mes = date('m');
        $this->ano = date('Y');
    }

    public function updating($property)
    {
        if (in_array($property, ['mes', 'ano', 'profissional_id', 'terapia_id', 'unidade_id'])) {
            $this->resetPage();
            $this->cacheProducao = [];
        }
    }

    public function limparFiltros()
    {
        $this->reset(['profissional_id', 'terapia_id', 'unidade_id']);
        $this->mes = date('m');
        $this->ano = date('Y');
        $this->resetPage();
    }

    public function getResumoProducao($prof)
    {
        // 1. Verifica se já calculamos a produção desse profissional no ciclo atual
        if (isset($this->cacheProducao[$prof->id])) {
            return $this->cacheProducao[$prof->id];
        }

        $query = Appointment::where('professional_id', $prof->id)
            ->whereYear('appointment_date', $this->ano)
            ->whereMonth('appointment_date', $this->mes)
            ->whereNotNull('check_in') 
            ->whereNotNull('check_out');

        if ($this->terapia_id) {
            $query->where('therapy_id', $this->terapia_id);
        }
        if ($this->unidade_id) {
            $query->where('unit_id', $this->unidade_id);
        }

        $atendimentos = $query->get();

        if ($atendimentos->isEmpty()) {
            $resultado = ['sessoes' => 0, 'valor_regra' => 'Sem produção', 'valor_total' => 0];
            $this->cacheProducao[$prof->id] = $resultado;
            return $resultado;
        }

        $regra = ProfessionalPaymentRule::where('professional_id', $prof->id)
            ->whereNull('therapy_id')
            ->whereNull('agreement_id')
            ->first();

        if (!$regra) {
            $resultado = [
                'sessoes' => $atendimentos->sum('session_number'), 
                'valor_regra' => 'Sem Regra Cadastrada', 
                'valor_total' => 0
            ];
            $this->cacheProducao[$prof->id] = $resultado;
            return $resultado;
        }

        $totalSessoes = $atendimentos->sum('session_number');
        $valorTotal = 0;
        $descricaoRegra = '';

        switch ($regra->payment_type) {
            case 'por_sessao':
                $valorTotal = $totalSessoes * $regra->amount;
                $descricaoRegra = 'Por Sessão (R$ ' . number_format($regra->amount, 2, ',', '.') . ')';
                break;

            case 'por_dia':
                $diasTrabalhados = $atendimentos->pluck('appointment_date')->map(function($date) {
                    return Carbon::parse($date)->format('Y-m-d');
                })->unique()->count();

                $valorTotal = $diasTrabalhados * $regra->amount;
                $descricaoRegra = 'Por Dia (' . $diasTrabalhados . ' dias trab.)';
                break;

            case 'por_hora':
                $totalMinutos = 0;
                
                foreach ($atendimentos as $atendimento) {
                    $inicio = Carbon::parse($atendimento->check_in);
                    $fim = Carbon::parse($atendimento->check_out);
                    
                    $totalMinutos += $inicio->diffInMinutes($fim);
                }
                
                $horasDecimais = $totalMinutos / 60;
                $horasArredondadas = ceil($horasDecimais);
                $valorTotal = $horasArredondadas * $regra->amount;
                
                $horasFormatadas = floor($totalMinutos / 60) . 'h' . str_pad($totalMinutos % 60, 2, '0', STR_PAD_LEFT);
                $descricaoRegra = "Por Hora ({$horasFormatadas} → Apurado: {$horasArredondadas}h)";
                break;
        }

        $resultado = [
            'sessoes' => $totalSessoes,
            'valor_regra' => $descricaoRegra,
            'valor_total' => $valorTotal
        ];

        // 2. Salva o cálculo no cache
        $this->cacheProducao[$prof->id] = $resultado;

        return $resultado;
    }

    public function abrirExtrato($profissionalId)
    {
        $profissional = Professional::findOrFail($profissionalId);
        $this->profissionalExtratoNome = $profissional->name;

        $detalhesQuery = Appointment::with(['patient', 'therapy'])
            ->where('professional_id', $profissionalId)
            ->whereMonth('appointment_date', $this->mes)
            ->whereYear('appointment_date', $this->ano)
            ->whereNotNull('check_in')
            ->whereNotNull('check_out');

        if ($this->terapia_id) $detalhesQuery->where('therapy_id', $this->terapia_id);
        if ($this->unidade_id) $detalhesQuery->whereHas('patient', fn($q) => $q->where('unit_id', $this->unidade_id));

        $this->extratoAtendimentos = $detalhesQuery->orderBy('appointment_date')->get();
        $this->modalExtratoAberto = true;
    }

    public function fecharExtrato()
    {
        $this->modalExtratoAberto = false;
        $this->extratoAtendimentos = [];
    }

    public function render()
    {
        $queryProfissionais = Professional::query()
            ->when($this->profissional_id, fn($q) => $q->where('id', $this->profissional_id))
            ->whereHas('appointments', function ($q) {
                $q->whereMonth('appointment_date', $this->mes)
                  ->whereYear('appointment_date', $this->ano)
                  ->whereNotNull('check_in')
                  ->whereNotNull('check_out');
                
                if ($this->terapia_id) $q->where('therapy_id', $this->terapia_id);
                if ($this->unidade_id) $q->whereHas('patient', fn($p) => $p->where('unit_id', $this->unidade_id));
            });

        // 1. Calcula o Total Global usando TODOS os registros filtrados (sem paginação)
        $todosProfissionais = (clone $queryProfissionais)->get();
        
        $somaValoresGlobais = 0;
        $somaSessoesGlobais = 0;
        
        foreach ($todosProfissionais as $prof) {
            $resumo = $this->getResumoProducao($prof); // O cache entra em ação aqui
            $somaValoresGlobais += $resumo['valor_total'];
            $somaSessoesGlobais += $resumo['sessoes'];
        }

        // 2. Aplica a paginação apenas para exibir os 10 itens na tabela da interface
        $profissionais = $queryProfissionais->orderBy('name')->paginate(10);

        return view('livewire.producao.fechamento', [
            'profissionaisLista' => Professional::orderBy('name')->get(),
            'terapiasLista'      => Therapy::orderBy('name')->get(),
            'unidadesLista'      => Unit::orderBy('name')->get(),
            'profissionais'      => $profissionais,
            'totalSessoesGlobais'=> $somaSessoesGlobais,
            'totalValorGlobais'  => $somaValoresGlobais,
        ]);
    }
}