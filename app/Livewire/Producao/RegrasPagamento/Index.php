<?php

namespace App\Livewire\Producao\RegrasPagamento;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\ProfessionalPaymentRule;
use App\Models\Professional;
use App\Models\Therapy;
use App\Models\Agreement;
use App\Models\ServiceType;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;

#[Layout('layouts.producao')]
class Index extends Component
{
    use WithPagination;

    public $modalAberto = false;
    public $modalExclusaoAberto = false;

    public string $busca = '';
    public $unidade_id = '';
    // Por padrão a listagem só mostra regras de profissional ATIVO — profissional
    // inativado continua na base (histórico não se apaga), mas some da tela por padrão.
    // O toggle existe pra quem precisa conferir/auditar uma regra antiga.
    public bool $mostrarInativos = false;

    public $regra_id = null;
    public $professional_id = '';
    public $payment_type = 'por_sessao';
    public $amount = '';
    public $valor_reajuste = '';
    public $therapy_id = '';
    public $agreement_id = '';
    public $service_type_id = ''; // <-- Nova propriedade

    public $profissionais = [];
    public $terapias = [];
    public $convenios = [];
    public $ambientes = []; // <-- Nova propriedade para a lista

    protected function rules()
    {
        return [
            'professional_id' => 'required|exists:professionals,id',
            'payment_type' => 'required|in:por_sessao,por_hora,por_dia',
            'amount' => 'required|numeric|min:0',
            'valor_reajuste' => 'nullable|numeric|min:0',
            'therapy_id' => 'nullable|exists:therapies,id',
            'agreement_id' => 'nullable|exists:agreements,id',
            // Certifique-se de que o nome da tabela no BD seja 'service_types', caso contrário, ajuste abaixo:
            'service_type_id' => 'nullable|exists:service_types,id', 
        ];
    }

    protected $messages = [
        'professional_id.required' => 'O profissional é obrigatório.',
        'payment_type.required' => 'O tipo de pagamento é obrigatório.',
        'amount.required' => 'O valor é obrigatório.',
        'amount.numeric' => 'Insira um valor numérico válido (ex: 150.50).',
    ];

    public function mount()
    {
        $this->autorizarAcesso();

        $this->profissionais = Professional::orderBy('name')->get();
        $this->terapias = Therapy::orderBy('name')->get();
        $this->convenios = Agreement::orderBy('name')->get();
        $this->ambientes = ServiceType::orderBy('name')->get(); // <-- Carregando lista do banco
    }

    /**
     * SEGURANÇA: componente não tinha nenhuma checagem de papel própria — só o
     * middleware `role:admin|manager` da rota `/regras-pagamento`, que não é
     * reexecutado pelas ações do Livewire (mesma classe de lacuna já corrigida em
     * outras telas de Produção, ver CLAUDE.md "Auditoria de acesso"). Sem isto, quem
     * já tivesse um snapshot do componente carregado (ex.: papel rebaixado no meio da
     * sessão) continuava criando/editando/excluindo regra de pagamento de qualquer
     * profissional via requisição direta ao Livewire.
     */
    private function autorizarAcesso(): void
    {
        if (! auth()->user()->hasAnyRole(['admin', 'manager'])) {
            abort(403, 'Você não tem permissão para acessar Regras de Pagamento.');
        }
    }

    public function updatingBusca()
    {
        $this->resetPage();
    }

    public function updatingUnidadeId()
    {
        $this->resetPage();
    }

    public function updatingMostrarInativos()
    {
        $this->resetPage();
    }

    public function limparFiltros()
    {
        $this->reset(['busca', 'unidade_id', 'mostrarInativos']);
        $this->resetPage();
    }

    public function abrirModalCriar()
    {
        $this->autorizarAcesso();

        $this->resetForm();
        $this->modalAberto = true;
    }

    public function abrirModalEditar($id)
    {
        $this->autorizarAcesso();

        $this->resetForm();
        $regra = ProfessionalPaymentRule::findOrFail($id);

        $this->regra_id = $regra->id;
        $this->professional_id = $regra->professional_id;
        $this->payment_type = $regra->payment_type;
        $this->amount = $regra->amount;
        $this->valor_reajuste = $regra->valor_reajuste;
        $this->therapy_id = $regra->therapy_id;
        $this->agreement_id = $regra->agreement_id;
        $this->service_type_id = $regra->service_type_id; // <-- Preenchendo ao editar

        $this->modalAberto = true;
    }

    public function salvar()
    {
        $this->autorizarAcesso();

        $this->validate();

        $valorNumerico = (float) str_replace(',', '.', $this->amount);
        $reajusteNumerico = ($this->valor_reajuste !== '' && $this->valor_reajuste !== null)
            ? (float) str_replace(',', '.', $this->valor_reajuste)
            : null;

        $dados = [
            'professional_id' => $this->professional_id,
            'payment_type' => $this->payment_type,
            'amount' => $valorNumerico,
            'valor_reajuste' => $reajusteNumerico,
            'therapy_id' => $this->therapy_id ?: null,
            'agreement_id' => $this->agreement_id ?: null,
            'service_type_id' => $this->service_type_id ?: null,
        ];

        if ($this->regra_id) {
            // valor_base NÃO entra aqui de propósito: fica congelado desde a criação da
            // regra, mesmo que o valor vigente (amount) seja ajustado manualmente depois.
            ProfessionalPaymentRule::whereKey($this->regra_id)->update($dados);
        } else {
            ProfessionalPaymentRule::create([...$dados, 'valor_base' => $valorNumerico]);
        }

        $this->fecharModal();
    }

    public function confirmarExclusao($id)
    {
        $this->autorizarAcesso();

        $this->regra_id = $id;
        $this->modalExclusaoAberto = true;
    }

    public function excluir()
    {
        $this->autorizarAcesso();

        ProfessionalPaymentRule::findOrFail($this->regra_id)->delete();
        $this->modalExclusaoAberto = false;
        $this->resetForm();
    }

    public function fecharModal()
    {
        $this->modalAberto = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->reset(['regra_id', 'professional_id', 'therapy_id', 'agreement_id', 'service_type_id', 'amount', 'valor_reajuste']);
        $this->payment_type = 'por_sessao';
        $this->resetValidation();
    }

    public function render()
    {
        // Ordena por subconsulta em vez de join: 3 regras pertencem a profissionais inativados
        // e um join com `professionals` faria essas linhas sumirem da tela.
        $nomeDoProfissional = Professional::withTrashed()
            ->select('name')
            ->whereColumn('professionals.id', 'professional_payment_rules.professional_id');

        $regras = ProfessionalPaymentRule::query()
            ->with([
                'professional' => fn ($q) => $q->withTrashed(),
                'therapy', 'agreement', 'serviceType',
            ])
            ->when($this->busca !== '', function ($q) {
                // Curingas do LIKE precisam ser neutralizados: sem isso "%" casa com tudo.
                $termo = '%' . str_replace(['%', '_'], ['\%', '\_'], trim($this->busca)) . '%';

                $q->whereIn('professional_id', Professional::withTrashed()
                    ->where('name', 'like', $termo)
                    ->select('id'));
            })
            ->when($this->unidade_id, fn ($q) => $q->whereIn('professional_id',
                DB::table('professional_unit')
                    ->where('unit_id', $this->unidade_id)
                    ->select('professional_id')))
            // Professional::query() (sem withTrashed) já exclui inativado pelo scope
            // padrão do SoftDeletes — reaproveita isso em vez de duplicar a condição.
            ->when(! $this->mostrarInativos, fn ($q) => $q->whereIn('professional_id', Professional::query()->select('id')))
            ->orderBy($nomeDoProfissional)
            ->orderBy('id')
            ->paginate(10);

        return view('livewire.producao.regras-pagamento.index', [
            'regras'        => $regras,
            'unidadesLista' => Unit::orderBy('name')->get(),
        ]);
    }
}