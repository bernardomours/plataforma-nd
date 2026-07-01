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

#[Layout('layouts.producao')]
class Fechamento extends Component
{ 
    use WithPagination;

    // Filtros
    public $mes;
    public $ano;
    public $profissional_id = '';
    public $terapia_id = '';
    public $unidade_id = '';

    // Controle do Modal de Extrato
    public $modalExtratoAberto = false;
    public $profissionalExtratoNome = '';
    public $extratoAtendimentos = [];

    // Cache local para não recalcular o mesmo profissional várias vezes na mesma requisição
    protected $cacheProducao = [];

    public function mount()
    {
        $this->mes = date('m');
        $this->ano = date('Y');
    }

    public function updating($property)
    {
        // Reseta a paginação e o cache sempre que um filtro for alterado
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

    // --- LÓGICA MATEMÁTICA DE APURAÇÃO ---
    public function getResumoProducao($prof)
    {
        // 1. Buscar os atendimentos do profissional no período filtrado
        $query = \App\Models\Appointment::where('professional_id', $prof->id)
            ->whereYear('appointment_date', $this->ano)
            ->whereMonth('appointment_date', $this->mes)
            ->whereNotNull('check_in')  // Usa a coluna correta
            ->whereNotNull('check_out'); // Usa a coluna correta

        // Aplica os filtros da tela se estiverem preenchidos
        if ($this->terapia_id) {
            $query->where('therapy_id', $this->terapia_id);
        }
        if ($this->unidade_id) {
            $query->where('unit_id', $this->unidade_id);
        }

        $atendimentos = $query->get();

        // Se não atendeu ninguém, retorna zerado
        if ($atendimentos->isEmpty()) {
            return ['sessoes' => 0, 'valor_regra' => 'Sem produção', 'valor_total' => 0];
        }

        // 2. Buscar a regra de pagamento (buscando a regra padrão geral do profissional)
        $regra = \App\Models\ProfessionalPaymentRule::where('professional_id', $prof->id)
            ->whereNull('therapy_id')
            ->whereNull('agreement_id')
            ->first();

        if (!$regra) {
            return ['sessoes' => $atendimentos->count(), 'valor_regra' => 'Sem Regra Cadastrada', 'valor_total' => 0];
        }

        $totalSessoes = $atendimentos->count();
        $valorTotal = 0;
        $descricaoRegra = '';

        // 3. A Matemática
        switch ($regra->payment_type) {
            
            case 'por_sessao':
                // Cálculo direto
                $valorTotal = $totalSessoes * $regra->amount;
                $descricaoRegra = 'Por Sessão (R$ ' . number_format($regra->amount, 2, ',', '.') . ')';
                break;

            case 'por_dia':
                // Extrai apenas a data (sem a hora) de cada atendimento e conta as datas únicas
                $diasTrabalhados = $atendimentos->pluck('appointment_date')->map(function($date) {
                    return \Carbon\Carbon::parse($date)->format('Y-m-d');
                })->unique()->count();

                $valorTotal = $diasTrabalhados * $regra->amount;
                $descricaoRegra = 'Por Dia (' . $diasTrabalhados . ' dias trab.)';
                break;

            case 'por_hora':
                // Passo A: Somar os minutos de todas as sessões
                $totalMinutos = 0;
                
                foreach ($atendimentos as $atendimento) {
                    // Carbon processa as strings do banco usando as colunas corretas
                    $inicio = \Carbon\Carbon::parse($atendimento->check_in);
                    $fim = \Carbon\Carbon::parse($atendimento->check_out);
                    
                    // diffInMinutes() calcula exatamente o intervalo entre eles
                    $totalMinutos += $inicio->diffInMinutes($fim);
                }
                
                // Passo B: Converter para horas decimais
                $horasDecimais = $totalMinutos / 60;
                
                // Passo C: Arredondar sempre para cima (Teto / Ceil)
                $horasArredondadas = ceil($horasDecimais);

                $valorTotal = $horasArredondadas * $regra->amount;
                
                // Extra: Para mostrar bonito no painel (ex: 12h40 vira 13h)
                $horasFormatadas = floor($totalMinutos / 60) . 'h' . str_pad($totalMinutos % 60, 2, '0', STR_PAD_LEFT);
                $descricaoRegra = "Por Hora ({$horasFormatadas} → Apurado: {$horasArredondadas}h)";
                
                break;
        }

        return [
            'sessoes' => $totalSessoes,
            'valor_regra' => $descricaoRegra,
            'valor_total' => $valorTotal
        ];
    }

    // --- AÇÕES DA TELA ---
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

    // --- RENDERIZAÇÃO ---
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

        // Paginação dos profissionais
        $profissionais = $queryProfissionais->orderBy('name')->paginate(10);

        // Calcula os KPIs do Topo (Total Bruto e Total Sessões da Clínica)
        $somaValoresGlobais = 0;
        $somaSessoesGlobais = 0;
        
        // Dica de performance: Para os totais globais, processamos os profissionais da página atual
        // Se precisar do total ABSOLUTO (de todas as páginas), remova a paginação na linha abaixo.
        foreach ($profissionais->items() as $prof) {
            $resumo = $this->getResumoProducao($prof);
            $somaValoresGlobais += $resumo['valor_total'];
            $somaSessoesGlobais += $resumo['sessoes'];
        }

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