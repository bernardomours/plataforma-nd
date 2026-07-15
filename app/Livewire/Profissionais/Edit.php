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
            'selectedTherapies' => 'nullable|array',
        ];
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
            'units' => Unit::all(),
            'therapies' => Therapy::all(),
            'roles' => ProfessionalRole::cases()
        ]);
    }
}