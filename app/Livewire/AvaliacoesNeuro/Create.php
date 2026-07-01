<?php

namespace App\Livewire\AvaliacoesNeuro;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\NeuroAssessment;
use App\Models\Patient;
use App\Models\Professional;

#[Layout('layouts.app')]
class Create extends Component
{
    public $patient_id = '';
    public $professional_id = '';

    public function save()
    {
        $this->validate([
            'patient_id' => 'required',
            'professional_id' => 'required',
        ]);

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
        return view('livewire.avaliacoes-neuro.create', [
            'pacientes' => Patient::orderBy('name')->get(),
            'profissionais' => Professional::orderBy('name')->get(),
        ]);
    }
}