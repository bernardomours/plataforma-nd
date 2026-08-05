<?php

namespace App\Livewire\ChSolicitada;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use App\Models\Patient;

#[Layout('layouts.app')]
class Pendencias extends Component
{
    use WithPagination;

    public $mesReferencia = '';
    public $search = '';

    public function mount()
    {
        $this->mesReferencia = now()->format('Y-m');
    }

    public function updatedMesReferencia()
    {
        $this->resetPage(); 
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    #[Computed]
    public function pacientesPendentes()
    {
        $data = \Carbon\Carbon::parse($this->mesReferencia);

        return Patient::query()
            ->where('is_active', true)
            ->when($this->search, function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            })
            ->whereDoesntHave('requestedServices', function ($q) use ($data) {
                $q->whereYear('month_year', $data->year)
                  ->whereMonth('month_year', $data->month);
            })
            ->orderBy('name')
            ->paginate(20);
    }

    #[On('ch-salva-com-sucesso')]
    public function notificarSucesso()
    {
        session()->flash('message', 'Carga Horária registrada! O paciente saiu da fila.');
    }

    #[On('ch-registrada-com-sucesso')]
    public function atualizarFila()
    {
    }

    public function render()
    {
        return view('livewire.ch-solicitada.pendencias');
    }
}