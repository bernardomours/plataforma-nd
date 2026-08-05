<?php

namespace App\Livewire\ChSolicitada;

use App\Models\Patient;
use App\Models\RequestedService;
use App\Models\Therapy;
use App\Models\ServiceType;
use Livewire\Component;
use Livewire\Attributes\On;

class RegistroModal extends Component
{
    public $isModalOpen = false;
    public ?Patient $patient = null;

    // Campos Fixos (Cabeçalho)
    public $month_year = '';
    public $requisition_number = '';

    // Repeater Dinâmico
    public $terapias = [];

    protected function rules()
    {
        return [
            'month_year' => 'required|date_format:Y-m',
            'requisition_number' => 'required|string',
            'terapias' => 'required|array|min:1',
            'terapias.*.therapy_id' => 'required|exists:therapies,id',
            'terapias.*.service_type_id' => 'required|exists:service_types,id',
            'terapias.*.requested_hours' => 'required|numeric|min:0',
            'terapias.*.approved_hours' => 'nullable|numeric|min:0',
            'terapias.*.planned_hours' => 'nullable|numeric|min:0',
        ];
    }
    
    protected $messages = [
        'terapias.*.therapy_id.required' => 'A terapia é obrigatória.',
        'terapias.*.service_type_id.required' => 'O tipo é obrigatório.',
        'terapias.*.requested_hours.required' => 'A CH é obrigatória.',
    ];

    // Escuta o evento disparado pelo botão na tabela
    #[On('abrir-modal-ch')]
    public function abrirModal($pacienteId, $mesReferencia)
    {
        $this->resetValidation();
        $this->resetForm();

        $this->patient = Patient::findOrFail($pacienteId);
        $this->month_year = $mesReferencia; // Já preenche o mês selecionado no filtro!

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
            'planned_hours' => '',
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

        // Converte "2026-08" para o formato DATE "2026-08-01" do banco
        $formattedDate = $this->month_year . '-01';

        foreach ($this->terapias as $terapia) {
            RequestedService::create([
                'patient_id' => $this->patient->id,
                'month_year' => $formattedDate,
                'requisition_number' => $this->requisition_number,
                'therapy_id' => $terapia['therapy_id'],
                'service_type_id' => $terapia['service_type_id'],
                'requested_hours' => $terapia['requested_hours'],
                'approved_hours' => $terapia['approved_hours'] ?: null,
                'planned_hours' => $terapia['planned_hours'] ?: null,
            ]);
        }

        $this->closeModal();  

        // Avisa a tela principal que deu tudo certo para recarregar a lista e exibir o Toast
        $this->dispatch('ch-salva-com-sucesso');
    }

    public function render()
    {
        return view('livewire.ch-solicitada.registro-modal', [
            'therapies' => Therapy::all(),
            'serviceTypes' => ServiceType::all(),
        ]);
    }
}