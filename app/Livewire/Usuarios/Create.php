<?php

namespace App\Livewire\Usuarios;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Unit;
use Spatie\Permission\Models\Role; // Importação do Spatie

#[Layout('layouts.app')]
class Create extends Component
{
    public $name = '';
    public $email = '';
    public $password = '';
    public $birth_date = '';
    public $selected_roles = []; // Mudou de string para Array
    public $can_access_production = false;
    public $selected_units = [];

    public function mount()
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Acesso restrito a administradores.');
        }
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'birth_date' => 'required|date', 
            'selected_roles' => 'required|array|min:1', // Exige pelo menos um cargo
            'selected_units' => 'nullable|array', 
        ]);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'birth_date' => $this->birth_date,
            'can_access_production' => $this->can_access_production,
            'unit_id' => $this->selected_units[0] ?? null, 
        ]);

        // Sincroniza as Unidades
        $user->units()->sync($this->selected_units);
        
        // Mágica do Spatie: Sincroniza os múltiplos papéis escolhidos
        $user->syncRoles($this->selected_roles);

        session()->flash('message', 'Usuário criado com sucesso.');
        return redirect()->route('usuarios.index');
    }

    public function render()
    {
        return view('livewire.usuarios.create', [
            'unidades' => Unit::orderBy('city')->get(),
            'todasRoles' => Role::all(), // Envia as roles oficiais para a tela
        ]);
    }
}