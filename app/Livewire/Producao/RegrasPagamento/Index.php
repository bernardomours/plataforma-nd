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

    public $regra_id = null;
    public $professional_id = '';
    public $payment_type = 'por_sessao';
    public $amount = '';
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
        $this->profissionais = Professional::orderBy('name')->get();
        $this->terapias = Therapy::orderBy('name')->get();
        $this->convenios = Agreement::orderBy('name')->get();
        $this->ambientes = ServiceType::orderBy('name')->get(); // <-- Carregando lista do banco
    }

    public function updatingBusca()
    {
        $this->resetPage();
    }

    public function updatingUnidadeId()
    {
        $this->resetPage();
    }

    public function limparFiltros()
    {
        $this->reset(['busca', 'unidade_id']);
        $this->resetPage();
    }

    public function abrirModalCriar()
    {
        $this->resetForm();
        $this->modalAberto = true;
    }

    public function abrirModalEditar($id)
    {
        $this->resetForm();
        $regra = ProfessionalPaymentRule::findOrFail($id);

        $this->regra_id = $regra->id;
        $this->professional_id = $regra->professional_id;
        $this->payment_type = $regra->payment_type;
        $this->amount = $regra->amount;
        $this->therapy_id = $regra->therapy_id;
        $this->agreement_id = $regra->agreement_id;
        $this->service_type_id = $regra->service_type_id; // <-- Preenchendo ao editar

        $this->modalAberto = true;
    }

    public function salvar()
    {
        $this->validate();

        ProfessionalPaymentRule::updateOrCreate(
            ['id' => $this->regra_id],
            [
                'professional_id' => $this->professional_id,
                'payment_type' => $this->payment_type,
                'amount' => str_replace(',', '.', $this->amount),
                'therapy_id' => $this->therapy_id ?: null,
                'agreement_id' => $this->agreement_id ?: null,
                'service_type_id' => $this->service_type_id ?: null, // <-- Salvando no banco
            ]
        );

        $this->fecharModal();
    }

    public function confirmarExclusao($id)
    {
        $this->regra_id = $id;
        $this->modalExclusaoAberto = true;
    }

    public function excluir()
    {
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
        $this->reset(['regra_id', 'professional_id', 'therapy_id', 'agreement_id', 'service_type_id', 'amount']);
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
            ->orderBy($nomeDoProfissional)
            ->orderBy('id')
            ->paginate(10);

        return view('livewire.producao.regras-pagamento.index', [
            'regras'        => $regras,
            'unidadesLista' => Unit::orderBy('name')->get(),
        ]);
    }
}