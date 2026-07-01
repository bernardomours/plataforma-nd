<?php

namespace App\Livewire\AvaliacoesNeuro;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\NeuroAssessment;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
{
    $allowedUnits = auth()->user()->getAllowedUnitIds();

    $avaliacoes = NeuroAssessment::with(['patient.unit', 'patient.agreement', 'professional'])
        ->whereHas('patient', function ($q) use ($allowedUnits) {
            
            if ($this->search) {
                $q->where('name', 'like', '%' . $this->search . '%');
            }
            
            if ($allowedUnits !== null) {
                $q->whereIn('unit_id', $allowedUnits);
            }
            
        })
        ->latest()
        ->paginate(10);

    return view('livewire.avaliacoes-neuro.index', [
        'avaliacoes' => $avaliacoes
    ]);
}
}