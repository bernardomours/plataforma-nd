<?php

namespace App\Livewire\AvaliacoesNeuro;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\NeuroAssessment;
use App\Models\NeuroSession;
use App\Models\Patient;
use App\Models\Professional;

#[Layout('layouts.app')]
class Edit extends Component
{
    public NeuroAssessment $assessment;

    // Campos do Formulário Principal
    public $patient_id;
    public $professional_id;
    public $status;
    public $current_session;

    // Estado do Modal de Sessão
    public $showSessionModal = false;
    public $editingSessionId = null;

    // Campos do Modal de Sessão
    public $session_number;
    public $session_date;
    public $session_professional_id;
    public $session_observations;

    public function mount(NeuroAssessment $assessment)
    {
        $this->assessment = $assessment;
        $this->patient_id = $assessment->patient_id;
        $this->professional_id = $assessment->professional_id;
        $this->status = $assessment->status;
        $this->current_session = $assessment->current_session;
    }

    public function updateAssessment()
    {
        $this->validate([
            'patient_id' => 'required',
            'professional_id' => 'required',
            'status' => 'required',
        ]);

        $this->assessment->update([
            'patient_id' => $this->patient_id,
            'professional_id' => $this->professional_id,
            'status' => $this->status,
        ]);

        session()->flash('message', 'Informações da avaliação atualizadas.');
    }

    public function deleteAssessment()
    {
        $this->assessment->delete();
        return redirect()->route('avaliacoes-neuro.index');
    }

    // --- LÓGICA DO DIÁRIO DE SESSÕES ---

    public function openSessionModal($sessionId = null)
    {
        $this->resetValidation();
        $this->editingSessionId = $sessionId;

        if ($sessionId) {
            $session = NeuroSession::find($sessionId);
            $this->session_number = $session->session_number;
            $this->session_date = $session->date->format('Y-m-d');
            $this->session_professional_id = $session->professional_id;
            $this->session_observations = $session->observations;
        } else {
            $ultimaSessao = $this->assessment->sessions()->max('session_number') ?? 0;
            $this->session_number = $ultimaSessao + 1;
            $this->session_date = now()->format('Y-m-d');
            $this->session_professional_id = $this->professional_id;
            $this->session_observations = '';
        }

        $this->showSessionModal = true;
    }

    public function closeSessionModal()
    {
        $this->showSessionModal = false;
    }

    public function saveSession()
    {
        $this->validate([
            'session_number' => 'required|numeric|min:1|max:10',
            'session_date' => 'required|date',
            'session_professional_id' => 'required',
            'session_observations' => 'nullable|string',
        ]);

        if ($this->editingSessionId) {
            NeuroSession::find($this->editingSessionId)->update([
                'session_number' => $this->session_number,
                'date' => $this->session_date,
                'professional_id' => $this->session_professional_id,
                'observations' => $this->session_observations,
            ]);
        } else {
            NeuroSession::create([
                'neuro_assessment_id' => $this->assessment->id,
                'professional_id' => $this->session_professional_id,
                'session_number' => $this->session_number,
                'date' => $this->session_date,
                'observations' => $this->session_observations,
            ]);
        }

        $this->atualizarContagemDeSessoes();
        $this->closeSessionModal();
    }

    public function deleteSession($sessionId)
    {
        NeuroSession::find($sessionId)?->delete();
        $this->atualizarContagemDeSessoes();
    }

    private function atualizarContagemDeSessoes()
    {
        $maxSession = $this->assessment->sessions()->max('session_number') ?? 0;
        $newStatus = $maxSession >= 10 ? 'Concluída' : 'Em andamento';

        $this->assessment->update([
            'current_session' => $maxSession,
            'status' => $newStatus,
        ]);

        $this->current_session = $maxSession;
        $this->status = $newStatus;
        $this->assessment->refresh();
    }

    public function render()
    {
        return view('livewire.avaliacoes-neuro.edit', [
            'pacientes' => Patient::orderBy('name')->get(),
            'profissionais' => Professional::orderBy('name')->get(),
            'sessoes' => $this->assessment->sessions()->with('professional')->orderBy('session_number', 'asc')->get(),
            'podeAdicionarSessao' => $this->current_session < 10,
        ]);
    }
}