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
use Barryvdh\DomPDF\Facade\Pdf;

#[Layout('layouts.producao')]
class Fechamento extends Component
{ 
    use WithPagination;

    public $mes;
    public $ano;
    public $profissional_id = '';
    public $terapia_id = '';
    public $unidade_id = '';
    public $profissionalExtratoId;
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

    public function alternarGlosa($id)
    {
        $atendimento = Appointment::findOrFail($id);
        
        $atendimento->is_glosado = !$atendimento->is_glosado;
        $atendimento->save();

        $this->cacheProducao = [];

        $this->abrirExtrato($atendimento->professional_id);
    }

    public function getResumoProducao($prof)
    {
        if (isset($this->cacheProducao[$prof->id])) {
            return $this->cacheProducao[$prof->id];
        }

        $query = Appointment::with(['therapy', 'serviceType', 'patient.agreement']) 
            ->where('professional_id', $prof->id)
            ->whereYear('appointment_date', $this->ano)
            ->whereMonth('appointment_date', $this->mes)
            ->whereNotNull('check_in') 
            ->where('is_glosado', false); 

        if ($this->terapia_id) {
            $query->where('therapy_id', $this->terapia_id);
        }

        $atendimentos = $query->get();

        if ($atendimentos->isEmpty()) {
            $resultado = ['sessoes' => 0, 'valor_regra' => 'Sem produção', 'valor_total' => 0];
            $this->cacheProducao[$prof->id] = $resultado;
            return $resultado;
        }

        $regras = ProfessionalPaymentRule::where('professional_id', $prof->id)->get();

        if ($regras->isEmpty()) {
            $resultado = [
                'sessoes' => $atendimentos->sum('session_number'), 
                'valor_regra' => 'Sem Regra Cadastrada', 
                'valor_total' => 0
            ];
            $this->cacheProducao[$prof->id] = $resultado;
            return $resultado;
        }

        $regrasOrdenadas = $regras->sortByDesc(function ($r) {
            $score = 0;
            if (!is_null($r->therapy_id)) $score++;
            if (!is_null($r->service_type_id)) $score++;
            if (!is_null($r->agreement_id)) $score++;
            return $score;
        });

        $totalSessoes = 0;
        $valorTotal = 0;
        $resumoTextualRegras = []; 

        foreach ($atendimentos as $atendimento) {
            $qtdSessoes = $atendimento->session_number ?? 1;
            $totalSessoes += $qtdSessoes;
            
            $pacienteConvenioId = $atendimento->patient->agreement_id ?? null;
            $regraAplicada = null;

            foreach ($regrasOrdenadas as $regra) {
                $matchTherapy = is_null($regra->therapy_id) || $regra->therapy_id == $atendimento->therapy_id;
                $matchAmbiente = is_null($regra->service_type_id) || $regra->service_type_id == $atendimento->service_type_id;
                $matchConvenio = is_null($regra->agreement_id) || $regra->agreement_id == $pacienteConvenioId;

                if ($matchTherapy && $matchAmbiente && $matchConvenio) {
                    $regraAplicada = $regra;
                    break;
                }
            }

            if ($regraAplicada) {
                if ($regraAplicada->payment_type == 'por_sessao' || $regraAplicada->payment_type == 'Por Sessão') {
                    $valorTotal += $qtdSessoes * $regraAplicada->amount;

                    $nomeEtiqueta = [];
                    if ($regraAplicada->agreement_id) {
                        $nomeEtiqueta[] = $atendimento->patient->agreement->name ?? 'Convênio Específico';
                    }
                    if ($regraAplicada->therapy_id) {
                        $nomeEtiqueta[] = $atendimento->therapy->name ?? 'Terapia Específica';
                    }
                    if ($regraAplicada->service_type_id) {
                        $nomeEtiqueta[] = $atendimento->serviceType->name ?? 'Ambiente Específico';
                    }
                    
                    $chaveFiltro = empty($nomeEtiqueta) ? 'Regra Geral' : implode(' + ', $nomeEtiqueta);
                    $resumoTextualRegras[$chaveFiltro] = $regraAplicada->amount;
                } 
            }
        }

        $textosExibicao = [];
        foreach ($resumoTextualRegras as $nome => $valor) {
            $textosExibicao[] = $nome . ' (R$ ' . number_format($valor, 2, ',', '.') . ')';
        }
        
        $descricaoRegraFinal = empty($textosExibicao) 
            ? 'Regras Incompatíveis' 
            : implode(' | ', $textosExibicao);

        $resultado = [
            'sessoes' => $totalSessoes,
            'valor_regra' => $descricaoRegraFinal,
            'valor_total' => $valorTotal
        ];

        $this->cacheProducao[$prof->id] = $resultado;

        return $resultado;
    }

    public function abrirExtrato($profissionalId)
    {
        $profissional = Professional::findOrFail($profissionalId);
        
        $this->profissionalExtratoNome = $profissional->name;
        
        $this->profissionalExtratoId = $profissionalId; 

        $detalhesQuery = Appointment::with(['patient.agreement', 'therapy']) 
            ->where('professional_id', $profissionalId)
            ->whereMonth('appointment_date', $this->mes)
            ->whereYear('appointment_date', $this->ano)
            ->whereNotNull('check_in'); 

        if ($this->terapia_id) {
            $detalhesQuery->where('therapy_id', $this->terapia_id);
        }
        
        $this->extratoAtendimentos = $detalhesQuery->orderBy('appointment_date')->get();
        $this->modalExtratoAberto = true;
    }

    public function fecharExtrato()
    {
        $this->modalExtratoAberto = false;
        $this->extratoAtendimentos = [];
    }

    public function exportarExtratoPdf($profissionalId)
    {
        $profissional = Professional::findOrFail($profissionalId);

        $atendimentos = Appointment::with(['patient', 'therapy', 'serviceType'])
            ->where('professional_id', $profissional->id)
            ->whereYear('appointment_date', $this->ano)
            ->whereMonth('appointment_date', $this->mes)
            ->whereNotNull('check_in')
            ->orderBy('appointment_date')
            ->get();

        $resumoFinanceiro = $this->getResumoProducao($profissional);

        $pdf = Pdf::loadView('pdf.extrato-profissional', [
            'profissional' => $profissional,
            'atendimentos' => $atendimentos,
            'resumo' => $resumoFinanceiro,
            'mes' => $this->mes,
            'ano' => $this->ano
        ]);

        $nomeArquivo = 'Extrato_' . str_replace(' ', '_', $profissional->name) . '_' . $this->mes . '_' . $this->ano . '.pdf';
        
        return response()->streamDownload(fn () => print($pdf->output()), $nomeArquivo);
    }

    public function render()
    {
        $queryProfissionais = Professional::query()
            ->when($this->profissional_id, fn($q) => $q->where('id', $this->profissional_id))
            ->when($this->unidade_id, function ($q) {
                $q->whereIn('id', function($subquery) {
                    $subquery->select('professional_id')
                             ->from('professional_unit')
                             ->where('unit_id', $this->unidade_id);
                });
            })
            ->whereHas('appointments', function ($q) {
                $q->whereMonth('appointment_date', $this->mes)
                  ->whereYear('appointment_date', $this->ano)
                  ->whereNotNull('check_in');
                  
                if ($this->terapia_id) {
                    $q->where('therapy_id', $this->terapia_id);
                }
            });

        $todosProfissionais = (clone $queryProfissionais)->get();
        
        $somaValoresGlobais = 0;
        $somaSessoesGlobais = 0;
        
        foreach ($todosProfissionais as $prof) {
            $resumo = $this->getResumoProducao($prof);
            $somaValoresGlobais += $resumo['valor_total'];
            $somaSessoesGlobais += $resumo['sessoes'];
        }

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