<?php

namespace App\Livewire\Coordenacao\Acompanhamentos;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\Visit;
use App\Models\Therapy;
use App\Models\ServiceType;
use App\Models\Professional;
use App\Models\Unit;
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

        if (request()->has('edit')) {
            $visitId = request()->query('edit');
            $this->editVisit($visitId); 
        }

        $user = auth()->user();

        if (in_array($user->role, ['coordinator', 'supervisor'])) {
            
            $profissional = Professional::where('user_id', $user->id)->first();
            
            if ($profissional) {
                $this->profissional_id = $profissional->id;
            }
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
        $this->reset(['mes', 'ano', 'tipo', 'status', 'unidade_id', 'search', 'selectedVisits', 'selectAll']);
        
        $user = auth()->user();

        if (in_array($user->role, ['coordinator', 'supervisor'])) {
            $profissional = Professional::where('user_id', $user->id)->first();
            $this->profissional_id = $profissional ? $profissional->id : '';
        } else {
            $this->profissional_id = '';
        }

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
        
        $this->formTipo = $visit->type instanceof \BackedEnum 
            ? $visit->type->value 
            : ($visit->type instanceof \UnitEnum ? $visit->type->name : $visit->type);
            
        $this->formStatus = $visit->status instanceof \BackedEnum 
            ? $visit->status->value 
            : ($visit->status instanceof \UnitEnum ? $visit->status->name : $visit->status);
            
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
            'formProfissionalId' => 'nullable|exists:professionals,id',
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
        $allowedUnits = auth()->user()->getAllowedUnitIds();

        return Visit::query()
            ->with(['patient', 'professional', 'serviceType', 'therapy'])
            
            ->whereHas('patient', function ($q) use ($allowedUnits) {
                if ($allowedUnits !== null) {
                    if (empty($allowedUnits)) {
                        $q->whereRaw('1 = 0');
                    } else {
                        $q->whereIn('unit_id', $allowedUnits);
                    }
                }
                if ($this->unidade_id) {
                    $q->where('unit_id', $this->unidade_id);
                }
                if ($this->search) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                }
            })
            
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->tipo, fn($q) => $q->where('type', $this->tipo))
            ->when($this->profissional_id, fn($q) => $q->where('professional_id', $this->profissional_id))
            ->when($this->mes, fn($q) => $q->whereMonth('happened_at', $this->mes))
            ->when($this->ano, fn($q) => $q->whereYear('happened_at', $this->ano))
            ->latest('created_at');
    }

    public function render()
    {
        $allowedUnits = auth()->user()->getAllowedUnitIds();

        $unidadesQuery = Unit::query();
        $profissionaisQuery = Professional::whereIn('role', ['coordinator', 'supervisor']);

        if ($allowedUnits !== null) {
            if (empty($allowedUnits)) {
                $unidadesQuery->whereRaw('1 = 0');
                $profissionaisQuery->whereRaw('1 = 0');
            } else {
                $unidadesQuery->whereIn('id', $allowedUnits);
                
                $profissionaisQuery->whereHas('units', function($q) use ($allowedUnits) {
                    $q->whereIn('unit_id', $allowedUnits);
                });
            }
        }

        return view('livewire.coordenacao.acompanhamentos.index', [
            'visits' => $this->getVisitsQuery()->paginate(15),
            'profissionais' => $profissionaisQuery->get(),
            'unidades' => $unidadesQuery->get(),           
            'terapias' => Therapy::all(),
            'ambientes' => ServiceType::all(),
        ]);
    }
}