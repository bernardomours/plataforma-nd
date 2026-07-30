<?php

namespace App\Livewire\ChSolicitada;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\RequestedService;
use App\Models\Unit;
use App\Models\Agreement;
use App\Models\Appointment;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public $unit_id = '';
    public $month = '';
    public $year = '';
    public $search = '';
    
    public $agreement_id = '';
    public $agreements = [];

    public $units = [];
    public $availableYears = [];

    public function mount()
    {
        $this->units = Unit::orderBy('name')->get();
        
        $this->agreements = Agreement::orderBy('name')->get();

        for ($i = 0; $i <= 5; $i++) {
            $year = now()->subYears($i)->year;
            $this->availableYears[$year] = $year;
        }

        $this->month = now()->month;
        $this->year = now()->year;
    }

    private function buildQuery()
    {
        return RequestedService::query()
            ->select('requested_services.*')
            ->with(['patient.unit', 'therapy', 'serviceType'])
            ->has('patient') 
            
            ->addSelect([
                'realized_hours' => Appointment::selectRaw('COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(check_out, check_in))) / 3600, 0)')
                    ->whereColumn('appointments.patient_id', 'requested_services.patient_id')
                    ->whereColumn('appointments.therapy_id', 'requested_services.therapy_id')
                    ->whereRaw('YEAR(appointments.appointment_date) = YEAR(requested_services.month_year)')
                    ->whereRaw('MONTH(appointments.appointment_date) = MONTH(requested_services.month_year)')
            ])
            ->when($this->search, function ($query) {
                $query->where(function($q) {
                    $q->whereHas('patient', function ($subQ) {
                        $subQ->where('name', 'like', '%' . $this->search . '%');
                    })->orWhere('requisition_number', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->unit_id, function ($query) {
                $query->whereHas('patient', function ($q) {
                    $q->where('unit_id', $this->unit_id);
                });
            })
            ->when($this->agreement_id, function ($query) {
                $query->whereHas('patient', function ($q) {
                    $q->where('agreement_id', $this->agreement_id); 
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
        $this->reset(['unit_id', 'month', 'year', 'search', 'agreement_id']);
        $this->resetPage(); 
    }

    public function updatedUnitId() { $this->resetPage(); }
    public function updatedMonth() { $this->resetPage(); }
    public function updatedYear() { $this->resetPage(); }
    public function updatedSearch() { $this->resetPage(); }
    public function updatedAgreement() { $this->resetPage(); }

    public function formatTime($decimalHours)
    {
        $hours = floor($decimalHours);
        
        $minutes = round(($decimalHours - $hours) * 60);
        
        if ($minutes == 60) {
            $hours++;
            $minutes = 0;
        }
        
            return sprintf('%02d:%02d', $hours, $minutes);
    }

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
        
        $totalHorasRealizadas = $totaisQuery->get()->sum('realized_hours');

        $registros = $query->orderBy('month_year', 'desc')->paginate(15);

        return view('livewire.ch-solicitada.index', [
            'registros' => $registros,
            'totalHorasSolicitadas' => $totalHorasSolicitadas,
            'totalHorasLiberadas' => $totalHorasLiberadas,
            'totalHorasPlanejadas' => $totalHorasPlanejadas,
            'totalHorasRealizadas' => $totalHorasRealizadas,
        ]);
    }
}