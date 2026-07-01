<?php

namespace App\Livewire\Pacientes;

use App\Models\Patient;
use App\Models\Schedule;
use App\Models\Professional;
use App\Models\Therapy;
use App\Models\ServiceType;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class Agenda extends Component
{
    public Patient $patient;

    public $isModalOpen = false;
    public $editingScheduleId = null;

    public $day_of_week;
    public $start_time;
    public $end_time;
    public $professional_id;
    public $therapy_id;
    public $service_type_id;

    protected $rules = [
        'day_of_week' => 'required|string',
        'start_time' => 'required',
        'end_time' => 'required|after:start_time',
        'professional_id' => 'required|exists:professionals,id',
        'therapy_id' => 'required|exists:therapies,id',
        'service_type_id' => 'required|exists:service_types,id',
    ];

    public function mount(Patient $patient)
    {
        $this->patient = $patient;
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
        $this->editingScheduleId = null;
        $this->day_of_week = '';
        $this->start_time = '';
        $this->end_time = '';
        $this->professional_id = '';
        $this->therapy_id = '';
        $this->service_type_id = '';
    }

    public function editSchedule(Schedule $schedule)
    {
        $this->resetValidation();
        
        $this->editingScheduleId = $schedule->id;
        $this->day_of_week = $schedule->day_of_week;
        $this->start_time = \Carbon\Carbon::parse($schedule->start_time)->format('H:i');
        $this->end_time = \Carbon\Carbon::parse($schedule->end_time)->format('H:i');
        $this->professional_id = $schedule->professional_id;
        $this->therapy_id = $schedule->therapy_id;
        $this->service_type_id = $schedule->service_type_id;

        $this->isModalOpen = true;
    }

    public function saveSchedule()
    {
        $this->validate();

        $data = [
            'day_of_week' => $this->day_of_week,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'patient_id' => $this->patient->id,
            'professional_id' => $this->professional_id,
            'therapy_id' => $this->therapy_id,
            'service_type_id' => $this->service_type_id,
        ];

        if ($this->editingScheduleId) {
            Schedule::find($this->editingScheduleId)->update($data);
            session()->flash('message', 'Horário atualizado com sucesso!');
        } else {
            Schedule::create($data);
            session()->flash('message', 'Horário adicionado com sucesso!');
        }

        $this->closeModal();
    }

    public function deleteSchedule($id)
    {
        Schedule::findOrFail($id)->delete();
        session()->flash('message', 'Horário removido com sucesso!');
    }

    public function render()
    {
        $schedules = $this->patient->schedules()
            ->with(['professional', 'therapy', 'serviceType'])
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week');

        return view('livewire.pacientes.agenda', [
            'schedulesGrouped' => $schedules,
            'professionals' => Professional::all(), 
            'therapies' => Therapy::all(),         
            'serviceTypes' => ServiceType::all(),   
        ]);
    }
}