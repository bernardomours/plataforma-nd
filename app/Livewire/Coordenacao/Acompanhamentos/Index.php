<?php

namespace App\Livewire\Coordenacao\Acompanhamentos;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\Visit;
use App\Models\Therapy;
use App\Models\ServiceType;
use App\Models\Professional;
use App\Models\Unit; // Adicionado para puxar o select de Unidades
use Carbon\Carbon;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public $mes = '';
    public $ano = '';
    public $tipo = '';
    public $status = 'pending';
    public $profissional_id = '';
    public $unidade_id = '';
    public $search = '';

    public $selectedVisits = [];
    public $selectAll = false;

    public $isEditModalOpen = false;
    public $editVisitId;

    public $formPacienteNome = '';
    public $formProfissionalId = '';
    public $formHappenedAt = '';
    public $formTipo = '';
    public $formStatus = '';
    public $formServiceTypeId = '';
    public $formTherapyId = '';
    public $formNotes = '';

    public $anosDisponiveis = [];

    public function mount()
    {
        for ($i = 0; $i <= 5; $i++) {
            $ano = now()->subYears($i)->year;
            $this->anosDisponiveis[$ano] = $ano;
        }
    }

    public function updatedSearch() { $this->resetPage(); }
    public function updatedMes() { $this->resetPage(); }
    public function updatedStatus() { $this->resetPage(); }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedVisits = $this->getVisitsQuery()->pluck('id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedVisits = [];
        }
    }

    public function limparFiltros()
    {
        $this->reset(['mes', 'ano', 'tipo', 'status', 'profissional_id', 'unidade_id', 'search', 'selectedVisits', 'selectAll']);
        $this->resetPage();
    }

    public function deleteSelected()
    {
        if (!empty($this->selectedVisits)) {
            Visit::whereIn('id', $this->selectedVisits)->delete();
            $this->selectedVisits = [];
            $this->selectAll = false;
        }
    }

    public function editVisit($id)
    {
        $visit = Visit::with('patient')->findOrFail($id);
        
        $this->editVisitId = $visit->id;
        $this->formPacienteNome = $visit->patient->name ?? 'Paciente não encontrado';
        $this->formProfissionalId = $visit->professional_id;
        $this->formHappenedAt = $visit->happened_at ? Carbon::parse($visit->happened_at)->format('Y-m-d') : '';
        $this->formTipo = $visit->type;
        $this->formStatus = $visit->status;
        $this->formServiceTypeId = $visit->service_type_id;
        $this->formTherapyId = $visit->therapy_id;
        $this->formNotes = $visit->notes;

        $this->isEditModalOpen = true;
    }

    public function closeModal()
    {
        $this->isEditModalOpen = false;
        $this->reset(['editVisitId', 'formPacienteNome', 'formProfissionalId', 'formHappenedAt', 'formTipo', 'formStatus', 'formServiceTypeId', 'formTherapyId', 'formNotes']);
    }

    public function salvarVisit()
    {
        $rules = [
            'formProfissionalId' => 'nullable|exists:professionals,id', // CORRIGIDO PARA professionals
            'formTipo' => 'required',
            'formStatus' => 'required',
            'formServiceTypeId' => 'nullable|exists:service_types,id',
            'formTherapyId' => 'required|exists:therapies,id',
            'formNotes' => 'nullable|string',
        ];

        if ($this->formStatus === 'completed') {
            $rules['formHappenedAt'] = 'required|date';
        } else {
            $rules['formHappenedAt'] = 'nullable|date';
        }

        $this->validate($rules, [
            'formHappenedAt.required' => 'A data da visita é obrigatória para concluir o acompanhamento.',
        ]);

        $visit = Visit::findOrFail($this->editVisitId);
        $visit->update([
            'professional_id' => $this->formProfissionalId,
            'happened_at' => $this->formHappenedAt ?: null,
            'type' => $this->formTipo,
            'status' => $this->formStatus,
            'service_type_id' => $this->formServiceTypeId,
            'therapy_id' => $this->formTherapyId,
            'notes' => $this->formNotes,
        ]);

        $this->closeModal();
    }

    private function getVisitsQuery()
    {
        // 1. Pega as unidades permitidas para o usuário atual
        $allowedUnits = auth()->user()->getAllowedUnitIds();

        return Visit::query()
            ->with(['patient', 'professional', 'serviceType', 'therapy'])
            
            // 2. BLINDAGEM DE UNIDADE: Entramos no escopo do paciente
            ->whereHas('patient', function ($q) use ($allowedUnits) {
                // Aplica a restrição do perfil
                if ($allowedUnits !== null) {
                    if (empty($allowedUnits)) {
                        $q->whereRaw('1 = 0');
                    } else {
                        $q->whereIn('unit_id', $allowedUnits);
                    }
                }
                // Aplica o filtro do dropdown selecionado na tela
                if ($this->unidade_id) {
                    $q->where('unit_id', $this->unidade_id);
                }
                // Aplica a pesquisa por nome
                if ($this->search) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                }
            })
            
            // Filtros normais da tela
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->tipo, fn($q) => $q->where('type', $this->tipo))
            ->when($this->profissional_id, fn($q) => $q->where('professional_id', $this->profissional_id))
            ->when($this->mes, fn($q) => $q->whereMonth('happened_at', $this->mes)) // Corrigido para happened_at
            ->when($this->ano, fn($q) => $q->whereYear('happened_at', $this->ano))  // Corrigido para happened_at
            ->latest('created_at');
    }

    public function render()
    {
        $allowedUnits = auth()->user()->getAllowedUnitIds();

        // 3. BLINDAGEM DOS DROPDOWNS
        $unidadesQuery = Unit::query();
        $profissionaisQuery = Professional::whereIn('role', ['coordinator', 'supervisor']);

        if ($allowedUnits !== null) {
            if (empty($allowedUnits)) {
                $unidadesQuery->whereRaw('1 = 0');
                $profissionaisQuery->whereRaw('1 = 0');
            } else {
                $unidadesQuery->whereIn('id', $allowedUnits);
                
                // Profissionais precisam estar vinculados a pelo menos uma unidade que o usuário tem acesso
                $profissionaisQuery->whereHas('units', function($q) use ($allowedUnits) {
                    $q->whereIn('unit_id', $allowedUnits);
                });
            }
        }

        return view('livewire.coordenacao.acompanhamentos.index', [
            'visits' => $this->getVisitsQuery()->paginate(15),
            'profissionais' => $profissionaisQuery->get(), // Variável segura
            'unidades' => $unidadesQuery->get(),           // Variável segura adicionada
            'terapias' => Therapy::all(),
            'ambientes' => ServiceType::all(),
        ]);
    }
}