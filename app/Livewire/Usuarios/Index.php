<?php

namespace App\Livewire\Usuarios;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\User;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $filtroStatus = 'ativos';

    public function mount()
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Acesso restrito a administradores.');
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFiltroStatus()
    {
        $this->resetPage();
    }

    public function toggleProductionAccess($userId)
    {
        $user = User::withTrashed()->find($userId);
        if ($user) {
            $user->can_access_production = !$user->can_access_production;
            $user->save();
        }
    }

    public function deleteUser($userId)
    {
        User::find($userId)?->delete();
    }

    public function restoreUser($userId)
    {
        User::withTrashed()->find($userId)?->restore();
    }

    public function forceDeleteUser($userId)
    {
        User::withTrashed()->find($userId)?->forceDelete();
    }

    public function render()
    {
        // Otimização: Carrega 'roles' do Spatie para evitar o problema N+1 Query
        $usuarios = User::with(['units', 'roles'])
            ->when($this->filtroStatus === 'excluidos', function ($query) {
                return $query->onlyTrashed();
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->latest()
            ->paginate(10);

        return view('livewire.usuarios.index', [
            'usuarios' => $usuarios
        ]);
    }
}