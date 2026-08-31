<?php

namespace App\Livewire\AvaliacoesNeuro;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\NeuroAssessment;
use App\Models\Patient;
use App\Models\Professional;
use Illuminate\Validation\ValidationException;

#[Layout('layouts.app')]
class Create extends Component
{
    public $patient_id = '';
    public $professional_id = '';

    public function mount()
    {
        // Checagem própria: ação do Livewire não passa pelo middleware da rota
        // (role:admin|manager|administrative|avaliador_neuro).
        if (! auth()->user()->hasAnyRole(['admin', 'manager', 'administrative', 'avaliador_neuro'])) {
            abort(403, 'Você não tem permissão para registrar Avaliações Neuro.');
        }
    }

    public function save()
    {
        $this->validate([
            'patient_id' => 'required',
            'professional_id' => 'required',
        ]);

        // SEGURANÇA: valida a unidade do paciente e a coerência paciente x profissional
        // antes de criar a avaliação (mesma regra do Edit, item 11).
        $patientUnitId = Patient::withoutGlobalScopes()
            ->whereKey($this->patient_id)
            ->value('unit_id');

        if (! auth()->user()->canAccessUnit($patientUnitId)) {
            abort(403, 'Paciente fora das unidades permitidas.');
        }

        $professionalPertence = Professional::whereKey($this->professional_id)
            ->whereHas('units', fn ($q) => $q->where('units.id', $patientUnitId))
            ->exists();

        if (! $professionalPertence) {
            throw ValidationException::withMessages([
                'professional_id' => 'O profissional selecionado não atende a unidade deste paciente.',
            ]);
        }

        $avaliacao = NeuroAssessment::create([
            'patient_id' => $this->patient_id,
            'professional_id' => $this->professional_id,
            'status' => 'Em andamento',
            'current_session' => 0,
        ]);

        return redirect()->route('avaliacoes-neuro.edit', $avaliacao->id);
    }

    public function render()
    {
        // SEGURANÇA (item 6): restringe a lista de profissionais às unidades do usuário.
        $allowedUnitIds = auth()->user()->getAllowedUnitIds();

        $profissionaisQuery = Professional::orderBy('name');

        if ($allowedUnitIds !== null) {
            $profissionaisQuery->whereHas('units', fn ($q) => $q->whereIn('units.id', $allowedUnitIds));
        }

        return view('livewire.avaliacoes-neuro.create', [
            'pacientes' => Patient::orderBy('name')->get(),
            'profissionais' => $profissionaisQuery->get(),
        ]);
    }
}