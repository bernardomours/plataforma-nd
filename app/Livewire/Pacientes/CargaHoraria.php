<?php

namespace App\Livewire\Pacientes;

use App\Models\Patient;
use App\Models\RequestedService;
use App\Models\Therapy;
use App\Models\ServiceType;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class CargaHoraria extends Component
{
    use WithPagination;

    public Patient $patient;

    public $filter_month_year = '';

    public $isModalOpen = false;
    public $editingRecordId = null;

    public $month_year;
    public $requisition_number;
    public $requested_hours;
    public $approved_hours;
    public $planned_hours;
    public $therapy_id;
    public $service_type_id;

    protected function rules()
    {
        return [
            'month_year' => 'required|date',
            'requisition_number' => 'required|string',
            'requested_hours' => 'required|numeric|min:0',
            'approved_hours' => 'nullable|numeric|min:0',
            'planned_hours' => 'nullable|numeric|min:0',
            'therapy_id' => 'required|exists:therapies,id',
            'service_type_id' => 'required|exists:service_types,id',
        ];
    }

    public function mount(Patient $patient)
    {
        $this->patient = $patient;
    }

    public function clearFilter()
    {
        $this->filter_month_year = '';
        $this->resetPage();
    }

    public function updatingFilterMonthYear()
    {
        $this->resetPage();
    }

    public function openModal()
    {
        $this->resetValidation();
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->editingRecordId = null;
        $this->month_year = '';
        $this->requisition_number = '';
        $this->requested_hours = '';
        $this->approved_hours = '';
        $this->planned_hours = '';
        $this->therapy_id = '';
        $this->service_type_id = '';
    }

    public function editRecord(RequestedService $record)
    {
        $this->resetValidation();
        
        $this->editingRecordId = $record->id;
        $this->month_year = $record->month_year ? Carbon::parse($record->month_year)->format('Y-m') : '';
        $this->requisition_number = $record->requisition_number;
        $this->requested_hours = $record->requested_hours;
        $this->approved_hours = $record->approved_hours;
        $this->planned_hours = $record->planned_hours;
        $this->therapy_id = $record->therapy_id;
        $this->service_type_id = $record->service_type_id;

        $this->isModalOpen = true;
    }

    public function saveRecord()
    {
        $this->validate();

        $formattedDate = $this->month_year . '-01';

        $data = [
            'patient_id' => $this->patient->id,
            'month_year' => $formattedDate,
            'requisition_number' => $this->requisition_number,
            'requested_hours' => $this->requested_hours,
            'approved_hours' => $this->approved_hours ?: null,
            'planned_hours' => $this->planned_hours ?: null,
            'therapy_id' => $this->therapy_id,
            'service_type_id' => $this->service_type_id,
        ];

        if ($this->editingRecordId) {
            RequestedService::find($this->editingRecordId)->update($data);
            session()->flash('message', 'Solicitação atualizada com sucesso!');
        } else {
            RequestedService::create($data);
            session()->flash('message', 'Solicitação criada com sucesso!');
        }

        $this->closeModal();
    }

    public function deleteRecord($id)
    {
        RequestedService::findOrFail($id)->delete();
        session()->flash('message', 'Registro excluído com sucesso!');
    }

    public function render()
    {
        $query = RequestedService::with(['therapy', 'serviceType'])
            ->where('patient_id', $this->patient->id);

        if (!empty($this->filter_month_year)) {
            $date = Carbon::parse($this->filter_month_year);
            $query->whereYear('month_year', $date->year)
                  ->whereMonth('month_year', $date->month);
        }

        $query->orderBy('month_year', 'desc');

        $records = $query->get();

        $totals = [
            'requested' => $records->sum('requested_hours'),
            'approved' => $records->sum('approved_hours'),
            'planned' => $records->sum('planned_hours'),
        ];

        return view('livewire.pacientes.carga-horaria', [
            'records' => $records,
            'totals' => $totals,
            'therapies' => Therapy::all(),
            'serviceTypes' => ServiceType::all(),
        ]);
    }
}