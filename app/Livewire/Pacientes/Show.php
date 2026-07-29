<?php

namespace App\Livewire\Pacientes;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Patient;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;

#[Layout('layouts.app')]
class Show extends Component
{
    public Patient $patient;

    #[Url(as: 'aba', history: true)]
    public $abaAtual = 'agenda';

    public function mount(Patient $patient)
    {
        $this->patient = $patient->load([
            'agreement',
            'patientServices.serviceType', 
            'patientServices.coordinator', 
            'patientServices.supervisor'
        ]);
    }

    public function setAba($aba)
    {
        $this->abaAtual = $aba;
    }

    #[On('paciente-atualizado')]
    public function recarregarPaciente()
    {
        $this->patient->refresh();
        $this->patient->load([
            'agreement',
            'patientServices.serviceType', 
            'patientServices.coordinator', 
            'patientServices.supervisor'
        ]);
    }

    public function render()
    {
        return view('livewire.pacientes.show');
    }
}