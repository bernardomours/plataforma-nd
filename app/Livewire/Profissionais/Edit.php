<?php

namespace App\Livewire\Profissionais;

use Livewire\Component;
use App\Models\Professional;
use App\Models\Unit;
use App\Models\Therapy;
use App\Models\User;
use App\Enums\ProfessionalRole;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

#[Layout('layouts.app')]
class Edit extends Component
{
    public $professionalId; 

    public $name, $cpf, $phone, $birth_date, $register_number, $email, $role;
    public $selectedUnits = [];
    public $selectedTherapies = [];

    public function mount($professional)
    {
        $record = Professional::findOrFail($professional);

        // SEGURANÇA (IDOR): Professional não tem coluna unit_id (removida na migration
        // create_professional_unit_table); o vínculo é a pivô professional_unit.
        // Sem esta checagem, trocar o ID na URL abria o cadastro de qualquer profissional
        // de qualquer clínica.
        $this->authorizeUnitAccess($record);

        $this->professionalId = $record->id;
        
        $this->name = $record->name;
        $this->cpf = $record->cpf;
        $this->phone = $record->phone;
        $this->birth_date = $record->birth_date ? $record->birth_date->format('Y-m-d') : null;
        $this->register_number = $record->register_number;
        $this->email = $record->email;
        $this->role = $record->role->value ?? $record->role;

        $this->selectedUnits = $record->units->pluck('id')->map(fn($id) => (string) $id)->toArray();
        $this->selectedTherapies = $record->therapies->pluck('id')->map(fn($id) => (string) $id)->toArray();
    }

    /**
     * SEGURANÇA (multi-tenant): aborta se o profissional não tiver NENHUMA unidade em
     * comum com as unidades permitidas ao usuário logado. Admin/manager passam direto
     * (getAllowedUnitIds() === null).
     */
    private function authorizeUnitAccess(Professional $record): void
    {
        if (! auth()->user()->canAccessAnyUnit($record->units->pluck('id')->toArray())) {
            abort(403, 'Você não tem permissão para acessar profissionais desta unidade.');
        }
    }

    /**
     * SEGURANÇA (multi-tenant): unidades que o usuário logado pode atribuir a um
     * profissional. Alimenta o select da view E a validação do backend, para que o
     * front não seja a única barreira.
     */
    private function unidadesPermitidas()
    {
        $allowedUnitIds = auth()->user()->getAllowedUnitIds();

        return $allowedUnitIds === null
            ? Unit::all()
            : Unit::whereIn('id', $allowedUnitIds)->get();
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'cpf' => 'required|string|max:14|unique:professionals,cpf,' . $this->professionalId,
            'phone' => 'required|string|max:20',
            'birth_date' => 'required|date',
            'register_number' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'role' => 'required',
            'selectedUnits' => 'required|array|min:1',
            // SEGURANÇA: impede que o usuário injete no payload uma unidade que não
            // administra (escalar o profissional para outra clínica). Preserva as
            // unidades que o profissional JÁ possui, para não quebrar o save de quem
            // edita um profissional multi-unidade vendo só parte das unidades dele.
            'selectedUnits.*' => ['integer', Rule::in($this->unidadesAtribuiveisIds())],
            'selectedTherapies' => 'nullable|array',
        ];
    }

    /**
     * SEGURANÇA: conjunto de unidades que podem ser gravadas neste profissional =
     * unidades permitidas ao usuário + unidades que o registro já tinha.
     */
    private function unidadesAtribuiveisIds(): array
    {
        $permitidas = $this->unidadesPermitidas()->pluck('id')->all();

        $jaVinculadas = Professional::findOrFail($this->professionalId)
            ->units()
            ->pluck('units.id')
            ->all();

        return array_values(array_unique(array_merge($permitidas, $jaVinculadas)));
    }

    public function messages()
    {
        return [
            'cpf.unique' => 'Esse CPF já pertence a outro profissional.',
            'selectedUnits.required' => 'Selecione pelo menos uma unidade.',
            'role.required' => 'O campo Função / Cargo é obrigatório.'
        ];
    }

    public function save()
    {
        $this->validate();

        $record = Professional::findOrFail($this->professionalId);

        // SEGURANÇA (IDOR): re-checa no save(). O mount() sozinho não protege, porque o
        // Livewire re-hidrata o componente a cada request sem executar mount() de novo.
        $this->authorizeUnitAccess($record);

        $record->update([
            'name' => $this->name,
            'cpf' => $this->cpf,
            'phone' => $this->phone,
            'birth_date' => $this->birth_date,
            'register_number' => $this->register_number,
            'email' => $this->email,
            'role' => $this->role,
        ]);

        $record->units()->sync($this->selectedUnits);
        
        $record->therapies()->sync($this->selectedTherapies ?: []);

        if (!empty($this->email)) {
            
            if ($record->user_id) {
                $user = User::find($record->user_id);
                if ($user) {
                    $user->update([
                        'name' => $this->name,
                        'email' => $this->email,
                        'birth_date' => $this->birth_date,
                        'unit_id' => $this->selectedUnits[0] ?? null, 
                    ]);

                    $user->units()->sync($this->selectedUnits); 
                }
            } else {
                $user = User::firstOrCreate(
                    ['email' => $this->email],
                    [
                        'name' => $this->name,
                        'password' => Hash::make('mudar123'),
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

                $record->update(['user_id' => $user->id]);
            }
        }

        session()->flash('message', "Profissional {$record->name} atualizado com sucesso.");
        
        return redirect()->route('profissionais.index');
    }

    public function render()
    {
        return view('livewire.profissionais.edit', [
            // SEGURANÇA: o select lista apenas as unidades permitidas ao usuário, MAIS as
            // que o profissional já possui — se ocultássemos as já vinculadas, um save
            // normal desvincularia o profissional dessas unidades sem o usuário perceber.
            'units' => Unit::whereIn('id', $this->unidadesAtribuiveisIds())->get(),
            'therapies' => Therapy::all(),
            'roles' => ProfessionalRole::cases()
        ]);
    }
}