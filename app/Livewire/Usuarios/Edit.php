<?php

namespace App\Livewire\Usuarios;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Unit;

#[Layout('layouts.app')]
class Edit extends Component
{
    public User $user;

    public $name = '';
    public $email = '';
    public $password = ''; // Opcional na edição
    public $birth_date = '';
    public $role = '';
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
        $this->role = $user->role;
        $this->can_access_production = (bool) $user->can_access_production;
        
        // Puxa as unidades que o utilizador já tem associadas
        $this->selected_units = $user->units->pluck('id')->toArray();
    }

    public function update()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email' . (isset($this->user) ? ',' . $this->user->id : ''),
            'password' => isset($this->user) ? 'nullable|string|min:8' : 'required|string|min:8',
            'birth_date' => 'required|date', 
            'role' => 'required|string',
            'selected_units' => 'nullable|array', 
        ]);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'birth_date' => $this->birth_date,
            'role' => $this->role,
            'can_access_production' => $this->can_access_production,
            'unit_id' => $this->selected_units[0] ?? null,
        ];

        // Só atualiza a senha se um novo valor foi introduzido
        if (!empty($this->password)) {
            $data['password'] = Hash::make($this->password);
        }

        $this->user->update($data);
        $this->user->units()->sync($this->selected_units);

        session()->flash('message', 'Usuário atualizado com sucesso.');
        return redirect()->route('usuarios.index');
    }

    public function render()
    {
        return view('livewire.usuarios.edit', [
            'unidades' => Unit::orderBy('city')->get(),
        ]);
    }
}