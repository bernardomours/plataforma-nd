<?php

namespace App\Livewire\Producao\AtendimentosRealizados;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Professional;
use App\Models\Agreement;
use App\Models\Therapy;
use App\Models\ServiceType;
use App\Models\Unit;

#[Layout('layouts.producao')]
class Index extends Component
{
    use WithPagination;

    // Filtros de Pesquisa
    public $search = '';
    public $patient_id = '';
    public $professional_id = '';
    public $agreement_id = '';
    public $therapy_id = '';
    public $service_type_id = '';
    public $unit_id = '';
    public $guide = '';
    public $start_date = '';
    public $end_date = '';

    // Controle de Exibição das Colunas
    public $selectedColumns = [];

    public function mount()
    {
        $this->resetColumns();
    }

    // Inicializa a visibilidade das colunas (incluindo a nova "Duração")
    public function resetColumns()
    {
        $this->selectedColumns = [
            'nome' => true,
            'data' => true,
            'guia' => true,
            'terapia' => true,
            'tipo_atendimento' => true,
            'check_in' => true,
            'check_out' => true,
            'duracao' => true, // Nossa coluna criada
            'qtd_sessoes' => true,
            'profissional' => true,
            'registrado_em' => true,
            'atualizado_em' => true,
        ];
    }

    public function applyFilters()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset([
            'patient_id', 'professional_id', 'agreement_id', 'therapy_id', 
            'service_type_id', 'unit_id', 'guide', 'start_date', 'end_date', 'search'
        ]);
        $this->resetPage();
    }

    public function render()
    {
        // Inicia a Query
        $query = Appointment::query()->with(['patient', 'professional', 'therapy', 'serviceType']);

        // Aplica os filtros se eles estiverem preenchidos
        if ($this->patient_id) $query->where('patient_id', $this->patient_id);
        if ($this->professional_id) $query->where('professional_id', $this->professional_id);
        if ($this->agreement_id) $query->where('agreement_id', $this->agreement_id);
        if ($this->therapy_id) $query->where('therapy_id', $this->therapy_id);
        if ($this->service_type_id) $query->where('service_type_id', $this->service_type_id);
        if ($this->unit_id) $query->where('unit_id', $this->unit_id);
        if ($this->guide) $query->where('guide', 'like', '%' . $this->guide . '%');
        if ($this->start_date) $query->whereDate('appointment_date', '>=', $this->start_date);
        if ($this->end_date) $query->whereDate('appointment_date', '<=', $this->end_date);
        
        // Pesquisa por nome do paciente
        if ($this->search) {
            $query->whereHas('patient', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            });
        }

        // 1. Calcula os totais ANTES de paginar (usando clone para a query não se contaminar)
        $totalConsultas = (clone $query)->count();
        $totalSessoes = (clone $query)->sum('session_number') ?? $totalConsultas;

        // 2. Só DEPOIS aplica a ordenação e paginação
        $appointments = $query->orderBy('appointment_date', 'desc')
                              ->orderBy('check_in', 'desc')
                              ->paginate(15);

        // Retorna a view enviando as listas para preencher os 'Selects'
        return view('livewire.producao.atendimentos-realizados.index', [
            'appointments' => $appointments,
            'totalConsultas' => $totalConsultas,
            'totalSessoes' => $totalSessoes,
            'patients' => Patient::orderBy('name')->get(),
            'professionals' => Professional::orderBy('name')->get(),
            'agreements' => Agreement::orderBy('name')->get(),
            'therapies' => Therapy::orderBy('name')->get(),
            'serviceTypes' => ServiceType::orderBy('name')->get(),
            'units' => Unit::orderBy('name')->get(),
        ]);
    }
}