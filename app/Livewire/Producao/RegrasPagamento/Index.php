<?php

namespace App\Livewire\Producao\RegrasPagamento;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\ProfessionalPaymentRule;
use App\Models\Professional;
use App\Models\Therapy;
use App\Models\Agreement;

#[Layout('layouts.producao')]
class Index extends Component
{
    use WithPagination;

    // Controles de Modal
    public $modalAberto = false;
    public $modalExclusaoAberto = false;

    // Campos do Formulário
    public $regra_id = null;
    public $professional_id = '';
    public $payment_type = 'por_sessao';
    public $amount = '';
    public $therapy_id = '';
    public $agreement_id = '';

    // Listas para os Selects
    public $profissionais = [];
    public $terapias = [];
    public $convenios = [];

    protected function rules()
    {
        return [
            'professional_id' => 'required|exists:professionals,id',
            'payment_type' => 'required|in:por_sessao,por_hora,por_dia',
            'amount' => 'required|numeric|min:0',
            'therapy_id' => 'nullable|exists:therapies,id',
            'agreement_id' => 'nullable|exists:agreements,id',
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
                'amount' => str_replace(',', '.', $this->amount), // Garante formato numérico correto
                'therapy_id' => $this->therapy_id ?: null, // Salva null se vazio
                'agreement_id' => $this->agreement_id ?: null, // Salva null se vazio
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
        $this->reset(['regra_id', 'professional_id', 'therapy_id', 'agreement_id', 'amount']);
        $this->payment_type = 'por_sessao';
        $this->resetValidation();
    }

    public function render()
    {
        $regras = ProfessionalPaymentRule::with(['professional', 'therapy', 'agreement'])
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('livewire.producao.regras-pagamento.index', [
            'regras' => $regras,
        ]);
    }
}