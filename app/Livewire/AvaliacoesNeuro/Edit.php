<?php

namespace App\Livewire\AvaliacoesNeuro;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\NeuroAssessment;
use App\Models\NeuroSession;
use App\Models\Patient;
use App\Models\Professional;
use Illuminate\Validation\ValidationException;

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
        // Checagem própria: ação do Livewire não passa pelo middleware da rota
        // (role:admin|manager|administrative|avaliador_neuro).
        if (! auth()->user()->hasAnyRole(['admin', 'manager', 'administrative', 'avaliador_neuro'])) {
            abort(403, 'Você não tem permissão para acessar Avaliações Neuro.');
        }

        // SEGURANÇA (IDOR + dado sensível de saúde): NeuroAssessment não tem unit_id nem
        // global scope; o route model binding entregava a avaliação de QUALQUER clínica
        // só trocando o ID na URL. A unidade é derivada do paciente vinculado.
        $this->authorizeAssessmentAccess($assessment);

        $this->assessment = $assessment;
        $this->patient_id = $assessment->patient_id;
        $this->professional_id = $assessment->professional_id;
        $this->status = $assessment->status;
        $this->current_session = $assessment->current_session;
    }

    /**
     * SEGURANÇA (multi-tenant): a unidade de uma avaliação é a unidade do paciente.
     * Lemos o unit_id com withoutGlobalScopes() de propósito: precisamos enxergar o
     * valor real (inclusive de outra unidade / paciente com saída registrada) justamente
     * para poder NEGAR o acesso — se aplicássemos o scope, viria null e perderíamos a
     * diferença entre "não existe" e "é de outra clínica".
     */
    private function authorizeAssessmentAccess(NeuroAssessment $assessment): void
    {
        $patientUnitId = Patient::withoutGlobalScopes()
            ->whereKey($assessment->patient_id)
            ->value('unit_id');

        if (! auth()->user()->canAccessUnit($patientUnitId)) {
            abort(403, 'Você não tem permissão para acessar avaliações desta unidade.');
        }
    }

    /**
     * SEGURANÇA (item 11 - validação cruzada): garante que o paciente destino pertence a
     * uma unidade permitida E que o profissional escolhido atende essa mesma unidade.
     * Sem isso era possível vincular um profissional de Natal a um paciente de Mossoró.
     */
    private function authorizeVinculo($patientId, $professionalId): void
    {
        $patientUnitId = Patient::withoutGlobalScopes()
            ->whereKey($patientId)
            ->value('unit_id');

        if (! auth()->user()->canAccessUnit($patientUnitId)) {
            abort(403, 'Paciente fora das unidades permitidas.');
        }

        $professionalPertence = Professional::whereKey($professionalId)
            ->whereHas('units', fn ($q) => $q->where('units.id', $patientUnitId))
            ->exists();

        if (! $professionalPertence) {
            // Erro de validação (não 403): é escolha inválida do formulário, então o
            // usuário deve ver a mensagem no campo, no padrão do resto do sistema.
            throw ValidationException::withMessages([
                'professional_id' => 'O profissional selecionado não atende a unidade deste paciente.',
            ]);
        }
    }

    public function updateAssessment()
    {
        $this->validate([
            'patient_id' => 'required',
            'professional_id' => 'required',
            'status' => 'required',
        ]);

        // SEGURANÇA: re-checa a avaliação atual (Livewire re-hidrata sem chamar mount())
        // e valida o par paciente/profissional de destino antes de gravar.
        $this->authorizeAssessmentAccess($this->assessment);
        $this->authorizeVinculo($this->patient_id, $this->professional_id);

        $this->assessment->update([
            'patient_id' => $this->patient_id,
            'professional_id' => $this->professional_id,
            'status' => $this->status,
        ]);

        session()->flash('message', 'Informações da avaliação atualizadas.');
    }

    public function deleteAssessment()
    {
        // SEGURANÇA: exclusão de prontuário neuropsicológico — re-checa a unidade.
        $this->authorizeAssessmentAccess($this->assessment);

        $this->assessment->delete();
        return redirect()->route('avaliacoes-neuro.index');
    }

    // --- LÓGICA DO DIÁRIO DE SESSÕES ---

    public function openSessionModal($sessionId = null)
    {
        $this->resetValidation();
        $this->editingSessionId = $sessionId;

        if ($sessionId) {
            // SEGURANÇA: busca a sessão DENTRO da avaliação atual. Com NeuroSession::find()
            // solto, um ID no payload devolvia as observações clínicas de outra avaliação.
            $session = $this->assessment->sessions()->whereKey($sessionId)->firstOrFail();
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

        // SEGURANÇA: grava sessão do diário — re-checa a unidade da avaliação.
        $this->authorizeAssessmentAccess($this->assessment);

        if ($this->editingSessionId) {
            // SEGURANÇA: só permite editar sessão que pertence a ESTA avaliação, senão o
            // ID no payload permitiria alterar a sessão de outra avaliação/clínica.
            $this->assessment->sessions()->whereKey($this->editingSessionId)->firstOrFail()->update([
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
        // SEGURANÇA: re-checa unidade e restringe a exclusão às sessões desta avaliação.
        $this->authorizeAssessmentAccess($this->assessment);

        $this->assessment->sessions()->whereKey($sessionId)->first()?->delete();
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
        // SEGURANÇA (item 6): Professional não é filtrado por global scope (vínculo é a
        // pivô professional_unit), então a lista precisa ser restringida manualmente.
        // Patient já é filtrado pela trait IsolatesByUnit.
        $allowedUnitIds = auth()->user()->getAllowedUnitIds();

        $profissionaisQuery = Professional::orderBy('name');

        if ($allowedUnitIds !== null) {
            $profissionaisQuery->whereHas('units', fn ($q) => $q->whereIn('units.id', $allowedUnitIds));
        }

        return view('livewire.avaliacoes-neuro.edit', [
            'pacientes' => Patient::orderBy('name')->get(),
            'profissionais' => $profissionaisQuery->get(),
            'sessoes' => $this->assessment->sessions()->with('professional')->orderBy('session_number', 'asc')->get(),
            'podeAdicionarSessao' => $this->current_session < 10,
        ]);
    }
}