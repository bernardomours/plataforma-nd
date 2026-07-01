<?php

namespace App\Livewire\ChSolicitada;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\RequestedService;
use App\Models\Unit;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public $unit_id = '';
    public $month = '';
    public $year = '';
    public $search = '';

    public $units = [];
    public $availableYears = [];

    public function mount()
    {
        $this->units = Unit::orderBy('name')->get();

        for ($i = 0; $i <= 5; $i++) {
            $year = now()->subYears($i)->year;
            $this->availableYears[$year] = $year;
        }

        $this->month = now()->month;
        $this->year = now()->year;
    }

    private function buildQuery()
    {
        return RequestedService::with(['patient.unit', 'therapy', 'serviceType'])
            ->when($this->search, function ($query) {
                $query->whereHas('patient', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                })->orWhere('requisition_number', 'like', '%' . $this->search . '%');
            })
            ->when($this->unit_id, function ($query) {
                $query->whereHas('patient', function ($q) {
                    $q->where('unit_id', $this->unit_id);
                });
            })
            ->when($this->year, function ($query) {
                $query->whereYear('month_year', $this->year);
            })
            ->when($this->month, function ($query) {
                $query->whereMonth('month_year', $this->month);
            });
    }

    public function clearFilters()
    {
        $this->reset(['unit_id', 'month', 'year', 'search']);
        $this->resetPage(); 
    }

    public function updatedUnitId() { $this->resetPage(); }
    public function updatedMonth() { $this->resetPage(); }
    public function updatedYear() { $this->resetPage(); }
    public function updatedSearch() { $this->resetPage(); }

    public function render()
{
    $allowedUnits = auth()->user()->getAllowedUnitIds();
    
    $query = $this->buildQuery();

    if ($allowedUnits !== null) {
        
        $query->whereHas('patient', function ($q) use ($allowedUnits) {
            $q->whereIn('unit_id', $allowedUnits);
        });
    }
   
    $totaisQuery = clone $query;
    
    $totalHorasSolicitadas = $totaisQuery->sum('requested_hours');
    $totalHorasLiberadas = $totaisQuery->sum('approved_hours');
    $totalHorasPlanejadas = $totaisQuery->sum('planned_hours');

    $registros = $query->orderBy('month_year', 'desc')->paginate(15);

    return view('livewire.ch-solicitada.index', [
        'registros' => $registros,
        'totalHorasSolicitadas' => $totalHorasSolicitadas,
        'totalHorasLiberadas' => $totalHorasLiberadas,
        'totalHorasPlanejadas' => $totalHorasPlanejadas,
    ]);
}
}