<?php

namespace App\Livewire\Profissionais;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Professional;
use App\Models\Unit;
use App\Models\Therapy;
use App\Models\User;
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
    
    // VARIÁVEL NOVA: Controle de itens por página
    public $perPage = 10; 

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

    public function mount()
    {
        $this->autorizarAcesso();
    }

    /**
     * SEGURANÇA: componente não tinha nenhuma checagem de papel própria — só o
     * middleware `role:admin|manager|administrative` da rota `/profissionais`, que
     * não é reexecutado pelas ações do Livewire. Especialmente sensível aqui porque
     * registrarSaida()/registrarRetorno() revogam e restauram ACESSO AO SISTEMA de
     * outros usuários (ver "Contas de acesso" no CLAUDE.md) — sem esta checagem, um
     * profissional comum conseguia inativar ou reativar a conta de um colega
     * chamando o método direto.
     */
    private function autorizarAcesso(): void
    {
        if (! auth()->user()->hasAnyRole(['admin', 'manager', 'administrative'])) {
            abort(403, 'Você não tem permissão para acessar Profissionais.');
        }
    }

    // Reseta paginação se alterar filtros ou quantidade por página
    public function updated($property)
    {
        // Adicionamos 'perPage' aqui na lista!
        if (in_array($property, ['search', 'unit_id', 'therapy_id', 'role', 'trashed_filter', 'perPage'])) {
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
        $this->autorizarAcesso();

        $this->validate([
            'motivo_saida' => 'required|string',
            'observacao_saida' => 'nullable|string',
        ]);

        $motivoCompleto = $this->motivo_saida;
        if (!empty($this->observacao_saida)) {
            $motivoCompleto .= ' - ' . $this->observacao_saida;
        }

        $profissionais = Professional::whereIn('id', $this->selectedProfessionals)->get();

        $acessosRevogados = 0;
        $acessosMantidos = [];

        foreach ($profissionais as $record) {
            $record->movementHistories()->create([
                'action' => 'Saída',
                'reason' => $motivoCompleto,
                'date' => now(),
                'user_id' => auth()->id(),
            ]);
            $record->delete(); // Soft Delete

            // SEGURANÇA: inativar o profissional precisa revogar também o acesso ao sistema.
            // Antes, o User criado junto com o cadastro continuava ativo, então alguém já
            // desligado seguia conseguindo entrar — e ainda aparecia no e-mail de
            // aniversariantes, porque o whereDoesntHave('professional') passa a considerar
            // que o usuário não tem profissional quando este está na lixeira.
            $resultado = $this->revogarAcessoDoUsuario($record);

            if ($resultado === 'revogado') {
                $acessosRevogados++;
            } elseif ($resultado !== null) {
                $acessosMantidos[] = "{$record->name}: {$resultado}";
            }
        }

        if ($acessosMantidos) {
            session()->flash('warning', 'Atenção — acesso NÃO revogado para: ' . implode(' | ', $acessosMantidos));
        }

        $this->closeSaidaModal();
        $this->selectedProfessionals = [];
        $this->selectAll = false;

        $msg = count($profissionais) . ' profissional(is) inativado(s) com sucesso.';
        if ($acessosRevogados > 0) {
            $msg .= " Acesso ao sistema revogado para {$acessosRevogados} conta(s).";
        }
        session()->flash('message', $msg);
    }

    /**
     * Desativa a conta de sistema vinculada ao profissional.
     *
     * O soft delete do User é o que efetivamente bloqueia o login: o provider de
     * autenticação do Laravel consulta o model, o SoftDeletingScope entra na query e a
     * conta deixa de ser encontrada — inclusive em sessões já abertas, que são revalidadas
     * a cada request.
     *
     * @return string|null  'revogado' em caso de sucesso, o motivo quando não foi possível,
     *                      ou null quando o profissional não tem conta vinculada.
     */
    private function revogarAcessoDoUsuario(Professional $record): ?string
    {
        if (! $record->user_id) {
            return null;
        }

        // Evita o auto-bloqueio: quem está executando a inativação não perde o próprio
        // acesso no meio da operação.
        if ((int) $record->user_id === (int) auth()->id()) {
            return 'é a sua própria conta — revogue por Usuários';
        }

        // A conta pode ser COMPARTILHADA: o cadastro de profissional usa
        // User::firstOrCreate(['email' => ...]), então dois profissionais com o mesmo
        // e-mail apontam para o mesmo usuário. Sem esta checagem, inativar um derrubaria
        // o acesso do outro, que continua trabalhando.
        $outroAtivo = Professional::where('user_id', $record->user_id)
            ->whereKeyNot($record->id)
            ->exists(); // o global scope de SoftDeletes já limita aos ativos

        if ($outroAtivo) {
            return 'conta compartilhada com outro profissional ativo';
        }

        $user = User::find($record->user_id);

        if (! $user) {
            return null;
        }

        // AUDITORIA: registra a revogação antes de apagar, com o motivo da saída.
        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->event('deleted')
            ->withProperties(['attributes' => [
                'acao'          => 'Acesso revogado por inativação do profissional',
                'profissional'  => $record->name,
                'email'         => $user->email,
            ]])
            ->log('Acesso ao sistema revogado por inativação do profissional');

        $user->delete();

        return 'revogado';
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
        $this->autorizarAcesso();

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

        // Contrapartida da revogação feita na saída: devolve o acesso ao sistema.
        // withTrashed() é necessário — a conta está justamente na lixeira.
        $acessoDevolvido = false;

        if ($record->user_id) {
            $user = User::withTrashed()->find($record->user_id);

            if ($user && $user->trashed()) {
                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($user)
                    ->event('restored')
                    ->withProperties(['attributes' => [
                        'acao'         => 'Acesso restaurado por retorno do profissional',
                        'profissional' => $record->name,
                        'motivo'       => $this->motivo_retorno,
                    ]])
                    ->log('Acesso ao sistema restaurado por retorno do profissional');

                $user->restore();
                $acessoDevolvido = true;
            }
        }

        $this->closeRetornoModal();
        session()->flash('message', 'Profissional reativado com sucesso.'
            . ($acessoDevolvido ? ' Acesso ao sistema restaurado.' : ''));
    }

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
        
        $profissionais = $query->orderBy($this->sortField, $this->sortDirection)->paginate($this->perPage);

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