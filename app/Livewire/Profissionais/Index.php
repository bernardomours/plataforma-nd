<?php

namespace App\Livewire\Profissionais;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Professional;
use App\Models\Unit;
use App\Models\Therapy;
use App\Enums\ProfessionalRole;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    // --- VARIÁVEIS DE PESQUISA, ORDENAÇÃO E FILTROS ---
    public $search = '';
    public $sortField = 'name';
    public $sortDirection = 'asc';
    public $unit_id = '';
    public $therapy_id = '';
    public $role = '';
    public $trashed_filter = '';

    // --- VARIÁVEIS DE AÇÕES EM MASSA (CHECKBOXES) ---
    public $selectedProfessionals = [];
    public $selectAll = false;

    // --- MODAL: REGISTRAR SAÍDA (BULK DELETE) ---
    public $isSaidaModalOpen = false;
    public $motivo_saida = '';
    public $observacao_saida = '';

    // --- MODAL: REGISTRAR RETORNO (RESTORE) ---
    public $isRetornoModalOpen = false;
    public $motivo_retorno = '';
    public $professionalIdToRestore = null;

    // Reseta paginação se alterar filtros
    public function updated($property)
    {
        if (in_array($property, ['search', 'unit_id', 'therapy_id', 'role', 'trashed_filter'])) {
            $this->resetPage();
        }
    }

    // Selecionar todos os registros da página atual
    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedProfessionals = $this->getProfessionalsQuery()->pluck('id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedProfessionals = [];
        }
    }

    public function clearFilters()
    {
        $this->reset(['search', 'unit_id', 'therapy_id', 'role', 'trashed_filter']);
        $this->resetPage();
    }

    public function sortBy($field)
    {
        $this->sortDirection = ($this->sortField == $field && $this->sortDirection == 'asc') ? 'desc' : 'asc';
        $this->sortField = $field;
    }

    // ==========================================
    // LÓGICA DE AÇÕES (CRIAR HISTÓRICO E DELETAR/RESTAURAR)
    // ==========================================

    public function openSaidaModal()
    {
        if (count($this->selectedProfessionals) === 0) return;
        $this->reset(['motivo_saida', 'observacao_saida']);
        $this->isSaidaModalOpen = true;
    }

    public function closeSaidaModal()
    {
        $this->isSaidaModalOpen = false;
    }

    public function registrarSaida()
    {
        $this->validate([
            'motivo_saida' => 'required|string',
            'observacao_saida' => 'nullable|string',
        ]);

        $motivoCompleto = $this->motivo_saida;
        if (!empty($this->observacao_saida)) {
            $motivoCompleto .= ' - ' . $this->observacao_saida;
        }

        $profissionais = Professional::whereIn('id', $this->selectedProfessionals)->get();

        foreach ($profissionais as $record) {
            $record->movementHistories()->create([
                'action' => 'Saída', 
                'reason' => $motivoCompleto,
                'date' => now(),
                'user_id' => auth()->id(),
            ]);
            $record->delete(); // Soft Delete
        }

        $this->closeSaidaModal();
        $this->selectedProfessionals = [];
        $this->selectAll = false;
        session()->flash('message', count($profissionais) . ' profissional(is) inativado(s) com sucesso.');
    }

    public function openRetornoModal($id)
    {
        $this->resetValidation();
        $this->professionalIdToRestore = $id;
        $this->motivo_retorno = '';
        $this->isRetornoModalOpen = true;
    }

    public function closeRetornoModal()
    {
        $this->isRetornoModalOpen = false;
        $this->professionalIdToRestore = null;
    }

    public function registrarRetorno()
    {
        $this->validate([
            'motivo_retorno' => 'required|string',
        ]);

        $record = Professional::withTrashed()->findOrFail($this->professionalIdToRestore);

        $record->movementHistories()->create([
            'action' => 'Retorno',
            'reason' => $this->motivo_retorno,
            'date' => now(),
            'user_id' => auth()->id(),
        ]);

        $record->restore(); // Retira do Soft Delete

        $this->closeRetornoModal();
        session()->flash('message', 'Profissional reativado com sucesso.');
    }

    // ==========================================
    // RENDERIZAÇÃO E CONSULTAS
    // ==========================================

    private function getProfessionalsQuery()
    {
        $query = Professional::with(['therapies', 'units']);

        $allowedUnitIds = auth()->user()->getAllowedUnitIds();
        if ($allowedUnitIds !== null) {
            $query->whereHas('units', function ($q) use ($allowedUnitIds) {
                $q->whereIn('units.id', $allowedUnitIds);
            });
        }

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('cpf', 'like', '%' . $this->search . '%');
            });
        }

        if (!empty($this->unit_id)) {
            $query->whereHas('units', function ($q) {
                $q->where('units.id', $this->unit_id);
            });
        }

        if (!empty($this->therapy_id)) {
            $query->whereHas('therapies', function ($q) {
                $q->where('therapies.id', $this->therapy_id);
            });
        }

        if (!empty($this->role)) {
            $query->where('role', $this->role);
        }

        if ($this->trashed_filter === 'with_trashed') {
            $query->withTrashed();
        } elseif ($this->trashed_filter === 'only_trashed') {
            $query->onlyTrashed();
        }

        return $query;
    }

    public function render()
    {
        $query = $this->getProfessionalsQuery();
        $profissionais = $query->orderBy($this->sortField, $this->sortDirection)->paginate(10);

        $allowedUnitIds = auth()->user()->getAllowedUnitIds();
        $unidadesDisponiveis = $allowedUnitIds === null 
            ? Unit::all() 
            : Unit::whereIn('id', $allowedUnitIds)->get();

        return view('livewire.profissionais.index', [
            'profissionais' => $profissionais,
            'unidadesFiltro' => $unidadesDisponiveis,
            'terapiasFiltro' => Therapy::all(),
            'cargosFiltro' => ProfessionalRole::cases() 
        ]);
    }
}