<?php

namespace App\Livewire\Coordenacao\Vinculos;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\PatientService;
use App\Models\Professional;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public $profissional_id = '';
    public $search = '';

    // Reseta a paginação sempre que o profissional ou a pesquisa mudar
    public function updatedProfissionalId()
    {
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $allowedUnits = auth()->user()->getAllowedUnitIds();

        // 1. Carrega os profissionais para o Select (respeitando as unidades permitidas)
        $profissionaisQuery = Professional::whereIn('role', ['coordinator', 'supervisor'])->orderBy('name');
        
        if ($allowedUnits !== null) {
            if (empty($allowedUnits)) {
                $profissionaisQuery->whereRaw('1 = 0');
            } else {
                $profissionaisQuery->whereHas('units', function($q) use ($allowedUnits) {
                    $q->whereIn('unit_id', $allowedUnits);
                });
            }
        }

        $vinculos = null;
        $totalVinculos = 0;

        // 2. Só faz a pesquisa se houver um profissional selecionado
        if (!empty($this->profissional_id)) {
            
            // A Trait IsolatesByUnit já está a proteger o PatientService por trás dos panos!
            $query = PatientService::with(['patient', 'serviceType', 'coordinator', 'supervisor'])
                ->where(function ($q) {
                    $q->where('coordinator_id', $this->profissional_id)
                      ->orWhere('supervisor_id', $this->profissional_id);
                });

            // Filtro de pesquisa pelo nome do paciente
            if ($this->search) {
                $query->whereHas('patient', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                });
            }

            // Conta o total absoluto (para o cartão azul) e pagina a lista (para a tabela)
            $totalVinculos = $query->count();
            $vinculos = $query->paginate(15);
        }

        return view('livewire.coordenacao.vinculos.index', [
            'profissionais' => $profissionaisQuery->get(),
            'vinculos' => $vinculos,
            'totalVinculos' => $totalVinculos,
        ]);
    }
}