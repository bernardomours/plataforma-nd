<?php

namespace App\Livewire\ChSolicitada;

use App\Models\Patient;
use App\Models\RequestedService;
use App\Models\Therapy;
use App\Models\ServiceType;
use App\Services\PlannedSessionsFromSchedule;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\Attributes\On;

class RegistroModal extends Component
{
    public $isModalOpen = false;
    public ?Patient $patient = null;
    public $isHumana = false; // Flag para controlar a tela

    /** O paciente tem agenda cadastrada? Controla o aviso na tela. */
    public $temAgenda = false;

    // Campos Fixos (Cabeçalho)
    public $month_year = '';
    public $requisition_number = '';

    // Repeater Dinâmico
    public $terapias = [];

    // Transformado em método para a regra ser dinâmica
    protected function rules()
    {
        $rules = [
            'month_year' => 'required|date_format:Y-m',
            'terapias' => 'required|array|min:1',
            'terapias.*.therapy_id' => 'required|exists:therapies,id',
            'terapias.*.service_type_id' => 'required|exists:service_types,id',
            'terapias.*.requested_hours' => 'required|numeric|min:0',
            'terapias.*.approved_hours' => 'nullable|numeric|min:0',
            'terapias.*.planned_hours' => 'nullable|numeric|min:0',
            // Total de sessões do MÊS, derivado da agenda mas editável.
            'terapias.*.planned_sessions' => 'nullable|integer|min:0',
        ];

        // Se for Humana, exige a requisição por terapia. Se não, exige a global.
        if ($this->isHumana) {
            $rules['terapias.*.requisition_number'] = 'required|string';
        } else {
            $rules['requisition_number'] = 'required|string';
        }

        return $rules;
    }
    
    protected $messages = [
        'terapias.*.therapy_id.required' => 'A terapia é obrigatória.',
        'terapias.*.service_type_id.required' => 'O tipo é obrigatório.',
        'terapias.*.requested_hours.required' => 'A CH é obrigatória.',
        'terapias.*.requisition_number.required' => 'A requisição é obrigatória para esta terapia.',
        'requisition_number.required' => 'O número da requisição é obrigatório.',
    ];

    #[On('abrir-modal-ch')]
    public function abrirModal($pacienteId, $mesReferencia)
    {
        $this->resetValidation();
        $this->resetForm();

        // Carrega o paciente já com o convênio para evitar consultas extras
        $this->patient = Patient::with('agreement')->findOrFail($pacienteId);
        $this->month_year = $mesReferencia; 

        // Verifica se é convênio Humana
        $nomeConvenio = mb_strtolower($this->patient->agreement?->name ?? '');
        $this->isHumana = str_contains($nomeConvenio, 'humana');

        $this->temAgenda = app(PlannedSessionsFromSchedule::class)->pacienteTemAgenda($this->patient);

        $this->adicionarTerapia(); // Inicia com 1 linha vazia
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->patient = null;
        $this->isHumana = false;
        $this->temAgenda = false;
        $this->month_year = '';
        $this->requisition_number = '';
        $this->terapias = [];
    }

    public function adicionarTerapia()
    {
        $this->terapias[] = [
            'therapy_id' => '',
            'service_type_id' => '',
            'requested_hours' => '',
            'approved_hours' => '',
            'planned_hours' => '',          // sessões por SEMANA (contexto)
            'planned_sessions' => '',       // sessões no MÊS (é o que vale no cálculo)
            'planned_from_schedule' => false,
            'agenda_blocos' => [],
            'requisition_number' => '', // Adicionado ao repeater
        ];
    }

    public function removerTerapia($index)
    {
        unset($this->terapias[$index]);
        $this->terapias = array_values($this->terapias); 
    }

    public function saveRecord()
    {
        $this->validate();

        $formattedDate = $this->month_year . '-01';

        foreach ($this->terapias as $terapia) {
            RequestedService::create([
                'patient_id' => $this->patient->id,
                'month_year' => $formattedDate,
                
                // Salva a requisição correta dependendo do convênio
                'requisition_number' => $this->isHumana ? $terapia['requisition_number'] : $this->requisition_number,
                
                'therapy_id' => $terapia['therapy_id'],
                'service_type_id' => $terapia['service_type_id'],
                'requested_hours' => $terapia['requested_hours'],
                'approved_hours' => $terapia['approved_hours'] ?: null,
                'planned_hours' => $terapia['planned_hours'] ?: null,
                // CONGELAMENTO: o total do mês é gravado agora e não muda mais sozinho,
                // ainda que a agenda seja alterada depois. Competência fechada fica fechada.
                'planned_sessions' => $terapia['planned_sessions'] !== '' ? (int) $terapia['planned_sessions'] : null,
                'planned_from_schedule' => (bool) ($terapia['planned_from_schedule'] ?? false),
            ]);
        }

        $this->closeModal();  
        $this->dispatch('ch-salva-com-sucesso');
    }

    /**
     * Recalcula o planejado sempre que a linha ganha terapia + tipo, ou o mês muda.
     */
    public function updated($property)
    {
        if (preg_match('/^terapias\.(\d+)\.(therapy_id|service_type_id)$/', $property, $m)) {
            $this->preencherPelaAgenda((int) $m[1]);
            return;
        }

        // Edição manual do total desmarca a origem "agenda" — a tela passa a sinalizar
        // que aquele número foi ajustado à mão.
        if (preg_match('/^terapias\.(\d+)\.planned_sessions$/', $property, $m)) {
            $this->terapias[(int) $m[1]]['planned_from_schedule'] = false;
            return;
        }

        if ($property === 'month_year') {
            foreach (array_keys($this->terapias) as $i) {
                $this->preencherPelaAgenda($i);
            }
        }
    }

    /**
     * Pré-preenche a linha com o que a agenda do paciente indica para a competência.
     * Não sobrescreve nada quando não há agenda para aquela terapia + tipo — nesse caso
     * o campo segue manual e a tela avisa.
     */
    private function preencherPelaAgenda(int $indice): void
    {
        $linha = $this->terapias[$indice] ?? null;

        if (! $linha || ! $this->patient || empty($this->month_year)) {
            return;
        }

        if (empty($linha['therapy_id']) || empty($linha['service_type_id'])) {
            return;
        }

        $derivado = app(PlannedSessionsFromSchedule::class)->paraCombinacao(
            $this->patient,
            $linha['therapy_id'],
            $linha['service_type_id'],
            Carbon::parse($this->month_year . '-01')
        );

        if ($derivado === null) {
            $this->terapias[$indice]['planned_from_schedule'] = false;
            $this->terapias[$indice]['agenda_blocos'] = [];
            return;
        }

        $this->terapias[$indice]['planned_sessions'] = $derivado['mensal'];
        $this->terapias[$indice]['planned_hours'] = $derivado['semanal'];
        $this->terapias[$indice]['planned_from_schedule'] = true;
        $this->terapias[$indice]['agenda_blocos'] = $derivado['blocos'];
    }

    public function render()
    {
        return view('livewire.ch-solicitada.registro-modal', [
            'therapies' => Therapy::all(),
            'serviceTypes' => ServiceType::all(),
        ]);
    }
}