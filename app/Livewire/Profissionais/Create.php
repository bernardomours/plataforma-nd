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
class Create extends Component
{
    public $name, $cpf, $phone, $birth_date, $register_number, $email, $role;
    
    public $selectedUnits = [];
    public $selectedTherapies = [];

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'cpf' => 'required|string|max:14|unique:professionals,cpf',
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
            'cpf.unique' => 'Esse CPF já está cadastrado.',
            'selectedUnits.required' => 'Selecione pelo menos uma unidade.',
            'role.required' => 'O campo Função / Cargo é obrigatório.'
        ];
    }

    private function performSave()
    {
        $this->validate();

        $professional = Professional::create([
            'name' => $this->name,
            'cpf' => $this->cpf,
            'phone' => $this->phone,
            'birth_date' => $this->birth_date,
            'register_number' => $this->register_number,
            'email' => $this->email,
            'role' => $this->role,
        ]);

        $professional->units()->sync($this->selectedUnits);
        
        if (!empty($this->selectedTherapies)) {
            $professional->therapies()->sync($this->selectedTherapies);
        }

        if (!empty($this->email)) {
            
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
            'name', 'cpf', 'phone', 'birth_date', 'register_number', 
            'email', 'role', 'selectedUnits', 'selectedTherapies'
        ]);
        
        $this->dispatch('clear-tom-selects');
    }

    public function render()
    {
        return view('livewire.profissionais.create', [
            'units' => Unit::all(),
            'therapies' => Therapy::all(),
            'roles' => ProfessionalRole::cases()
        ]);
    }
}