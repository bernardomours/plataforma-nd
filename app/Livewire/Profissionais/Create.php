<?php

namespace App\Livewire\Profissionais;

use Livewire\Component;
use App\Models\Professional;
use App\Models\ProfessionalPaymentRule;
use App\Models\Unit;
use App\Models\Therapy;
use App\Models\Agreement;
use App\Models\ServiceType;
use App\Models\User;
use App\Enums\ProfessionalRole;
use App\Enums\ProfessionalFormacao;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

#[Layout('layouts.app')]
class Create extends Component
{
    public $name, $cpf, $phone, $birth_date, $contract_date, $register_number, $email, $role, $formacao;

    public $selectedUnits = [];
    public $selectedTherapies = [];

    // Regra de pagamento (opcional): preenchendo o valor, já sobe pra Produção junto
    // com o cadastro, sem precisar passar por Regras de Pagamento depois.
    public $payment_amount = '';
    public $payment_type = 'por_sessao';
    public $payment_valor_reajuste = '';
    public $payment_therapy_id = '';
    public $payment_service_type_id = '';
    public $payment_agreement_id = '';

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'cpf' => 'required|string|max:14|unique:professionals,cpf',
            'phone' => 'required|string|max:20',
            'birth_date' => 'required|date',
            // Nullable de propósito: cadastro antigo não tinha esse campo, e nem todo
            // cadastro novo necessariamente sabe a data exata na hora de cadastrar.
            'contract_date' => 'nullable|date',
            'register_number' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'role' => 'required',
            'formacao' => ['nullable', Rule::in(array_column(ProfessionalFormacao::cases(), 'value'))],
            'selectedUnits' => 'required|array|min:1',
            // SEGURANÇA: impede cadastrar profissional em unidade que o usuário não
            // administra (validação no backend, não só no select da view).
            'selectedUnits.*' => ['integer', Rule::in($this->unidadesPermitidas()->pluck('id')->all())],
            'selectedTherapies' => 'nullable|array',
            // Regra de pagamento inteira é opcional — só payment_type fica amarrado a
            // ter um valor, pra não gravar regra sem saber como ela é cobrada.
            'payment_amount' => 'nullable|numeric|min:0',
            'payment_type' => 'nullable|required_with:payment_amount|in:por_sessao,por_hora,por_dia',
            'payment_valor_reajuste' => 'nullable|numeric|min:0',
            'payment_therapy_id' => 'nullable|exists:therapies,id',
            'payment_service_type_id' => 'nullable|exists:service_types,id',
            'payment_agreement_id' => 'nullable|exists:agreements,id',
        ];
    }

    /**
     * SEGURANÇA (multi-tenant): unidades atribuíveis pelo usuário logado.
     * null em getAllowedUnitIds() = admin/manager = acesso global.
     */
    private function unidadesPermitidas()
    {
        $allowedUnitIds = auth()->user()->getAllowedUnitIds();

        return $allowedUnitIds === null
            ? Unit::all()
            : Unit::whereIn('id', $allowedUnitIds)->get();
    }

    public function messages()
    {
        return [
            'cpf.unique' => 'Esse CPF já está cadastrado.',
            'selectedUnits.required' => 'Selecione pelo menos uma unidade.',
            'role.required' => 'O campo Função / Cargo é obrigatório.'
        ];
    }

    /**
     * SEGURANÇA: componente não tinha nenhuma checagem de papel própria — só o
     * middleware `role:admin|manager|administrative` da rota `/profissionais/cadastrar`,
     * que não é reexecutado pelas ações do Livewire (mesma lacuna documentada em
     * "Auditoria de acesso do papel profissional" no CLAUDE.md pra outras telas).
     */
    private function autorizarAcesso(): void
    {
        if (! auth()->user()->hasAnyRole(['admin', 'manager', 'administrative'])) {
            abort(403, 'Você não tem permissão para cadastrar profissionais.');
        }
    }

    private function performSave()
    {
        $this->autorizarAcesso();

        $this->validate();

        $professional = Professional::create([
            'name' => $this->name,
            'cpf' => $this->cpf,
            'phone' => $this->phone,
            'birth_date' => $this->birth_date,
            'contract_date' => $this->contract_date ?: null,
            'register_number' => $this->register_number,
            'email' => $this->email,
            'role' => $this->role,
            'formacao' => $this->formacao ?: null,
        ]);

        $professional->units()->sync($this->selectedUnits);

        if (!empty($this->selectedTherapies)) {
            $professional->therapies()->sync($this->selectedTherapies);
        }

        // Regra de pagamento já sobe pra Produção no ato do cadastro, se um valor foi
        // informado — evita ter que repetir o cadastro em Regras de Pagamento depois.
        if ($this->payment_amount !== '' && $this->payment_amount !== null) {
            $valorInicial = (float) str_replace(',', '.', (string) $this->payment_amount);

            ProfessionalPaymentRule::create([
                'professional_id' => $professional->id,
                'payment_type' => $this->payment_type ?: 'por_sessao',
                'amount' => $valorInicial,
                'valor_base' => $valorInicial,
                'valor_reajuste' => $this->payment_valor_reajuste !== '' && $this->payment_valor_reajuste !== null
                    ? (float) str_replace(',', '.', (string) $this->payment_valor_reajuste)
                    : null,
                'therapy_id' => $this->payment_therapy_id ?: null,
                'service_type_id' => $this->payment_service_type_id ?: null,
                'agreement_id' => $this->payment_agreement_id ?: null,
            ]);
        }

        if (!empty($this->email)) {
            
            $user = User::firstOrCreate(
                ['email' => $this->email],
                [
                    'name' => $this->name,
                    'password' => Hash::make('mudar123'),
                    // Obriga a definir uma senha pessoal no primeiro acesso (middleware
                    // EnsurePasswordIsChanged). Sem isto a conta ficaria indefinidamente
                    // acessível com a senha padrão, que é pública e previsível.
                    'must_change_password' => true,
                    'birth_date' => $this->birth_date,
                    'unit_id' => $this->selectedUnits[0] ?? null,
                ]
            );

            if (!$user->hasRole('profissional')) {
                $user->assignRole('profissional');
            }

            if (!empty($this->selectedUnits)) {
                $user->units()->syncWithoutDetaching($this->selectedUnits);
            }

            $professional->update(['user_id' => $user->id]);
        }

        return $professional;
    }

    public function save()
    {
        $professional = $this->performSave();

        session()->flash('message', "Profissional {$professional->name} cadastrado com sucesso.");
        
        return redirect()->route('profissionais.index');
    }

    public function saveAndCreateAnother()
    {
        $professional = $this->performSave();

        session()->flash('message', "Profissional {$professional->name} cadastrado com sucesso.");

        $this->reset([
            'name', 'cpf', 'phone', 'birth_date', 'contract_date', 'register_number',
            'email', 'role', 'formacao', 'selectedUnits', 'selectedTherapies',
            'payment_amount', 'payment_valor_reajuste', 'payment_therapy_id',
            'payment_service_type_id', 'payment_agreement_id',
        ]);
        $this->payment_type = 'por_sessao';

        $this->dispatch('clear-tom-selects');
    }

    public function render()
    {
        return view('livewire.profissionais.create', [
            // SEGURANÇA: select restrito às unidades permitidas ao usuário.
            'units' => $this->unidadesPermitidas(),
            'therapies' => Therapy::all(),
            'agreements' => Agreement::orderBy('name')->get(),
            'serviceTypes' => ServiceType::orderBy('name')->get(),
            'formacaoOptions' => ProfessionalFormacao::cases(),
            'roles' => ProfessionalRole::cases()
        ]);
    }
}