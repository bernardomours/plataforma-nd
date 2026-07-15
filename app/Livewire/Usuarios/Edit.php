<?php

namespace App\Livewire\Usuarios;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Unit;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
class Edit extends Component
{
    public User $user;

    public $name = '';
    public $email = '';
    public $password = '';
    public $birth_date = '';
    public $selected_roles = []; 
    public $can_access_production = false;
    public $selected_units = [];

    public function mount(User $user)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Acesso restrito a administradores.');
        }

        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->birth_date = $user->birth_date ? $user->birth_date->format('Y-m-d') : null;
        $this->can_access_production = (bool) $user->can_access_production;
        
        $this->selected_units = $user->units->pluck('id')->toArray();
        
        // Puxa as roles que o usuário já tem através do Spatie
        $this->selected_roles = $user->roles->pluck('name')->toArray();
    }

    public function update()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->user->id,
            'password' => 'nullable|string|min:8',
            'birth_date' => 'required|date', 
            'selected_roles' => 'required|array|min:1', // Exige pelo menos um cargo
            'selected_units' => 'nullable|array', 
        ]);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'birth_date' => $this->birth_date,
            'can_access_production' => $this->can_access_production,
            'unit_id' => $this->selected_units[0] ?? null,
        ];

        if (!empty($this->password)) {
            $data['password'] = Hash::make($this->password);
        }

        $this->user->update($data);
        
        // Atualiza as ligações
        $this->user->units()->sync($this->selected_units);
        $this->user->syncRoles($this->selected_roles); // Sincroniza os crachás

        session()->flash('message', 'Usuário atualizado com sucesso.');
        return redirect()->route('usuarios.index');
    }

    public function render()
    {
        return view('livewire.usuarios.edit', [
            'unidades' => Unit::orderBy('city')->get(),
            'todasRoles' => Role::all(), // Envia as roles oficiais para a tela
        ]);
    }
}