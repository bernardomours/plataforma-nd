<?php

namespace App\Livewire\Controles;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Spatie\Activitylog\Models\Activity;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $tab = 'todos'; // todos | atualizacoes | entradas_saidas

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function setTab($tab)
    {
        $this->tab = $tab;
        $this->resetPage();
    }

    public function render()
    {
        // Puxamos a auditoria junto com quem fez a ação (causer) e o registo afetado (subject)
        $query = Activity::with(['causer', 'subject'])->latest();

        // Lógica das abas
        if ($this->tab === 'atualizacoes') {
            $query->where('event', 'updated');
        } elseif ($this->tab === 'entradas_saidas') {
            $query->whereIn('event', ['created', 'deleted', 'restored'])
                  ->orWhere('subject_type', 'App\Models\MovementHistory');
        }

        // Lógica de pesquisa (Pesquisa pelo nome de quem fez ou pelo tipo de registo)
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->whereHas('causer', function ($causerQuery) {
                    $causerQuery->where('name', 'like', '%' . $this->search . '%');
                })
                ->orWhere('subject_type', 'like', '%' . $this->search . '%');
            });
        }

        return view('livewire.controles.index', [
            'atividades' => $query->paginate(15)
        ]);
    }
}