<?php

namespace App\Livewire\Usuarios;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Unit;

#[Layout('layouts.app')]
class Create extends Component
{
    public $name = '';
    public $email = '';
    public $password = '';
    public $birth_date = '';
    public $role = '';
    public $can_access_production = false;
    public $selected_units = []; // Array para as unidades

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
            'email' => 'required|email|unique:users,email' . (isset($this->user) ? ',' . $this->user->id : ''),
            'password' => isset($this->user) ? 'nullable|string|min:8' : 'required|string|min:8',
            'birth_date' => 'required|date', 
            'role' => 'required|string',
            'selected_units' => 'nullable|array', 
        ]);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'birth_date' => $this->birth_date,
            'role' => $this->role,
            'can_access_production' => $this->can_access_production,
            'unit_id' => $this->selected_units[0] ?? null, 
        ]);

        // Sincroniza a tabela pivô (many-to-many)
        $user->units()->sync($this->selected_units);

        return redirect()->route('usuarios.index');
    }

    public function render()
    {
        return view('livewire.usuarios.create', [
            'unidades' => Unit::orderBy('city')->get(),
        ]);
    }
}