<?php

namespace App\Livewire\TerapiasRealizadas;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Therapy;
use App\Models\ServiceType;
use App\Models\Professional;
use Carbon\Carbon;

#[Layout('layouts.app')]
class Create extends Component
{
    public $patient_id = '';
    public $therapy_id = '';
    public $service_type_id = '';
    public $professional_id = '';
    
    public $data_rapida = 'hoje';
    public $appointment_date;
    public $check_in;
    public $check_out;
    public $session_number;

    public function mount()
    {
        $this->appointment_date = now()->timezone('America/Fortaleza')->format('Y-m-d');
    }

    public function rules()
    {
        return [
            'patient_id' => 'required|exists:patients,id',
            'therapy_id' => 'required|exists:therapies,id',
            'service_type_id' => 'required|exists:service_types,id',
            'professional_id' => 'required|exists:professionals,id',
            'appointment_date' => 'required|date',
            'check_in' => 'required|date_format:H:i',
            'check_out' => 'required|date_format:H:i|after:check_in',
            'session_number' => 'required|integer|min:0',
        ];
    }

    public function messages()
    {
        return [
            'check_out.after' => 'O Check-out deve ser maior que o Check-in.',
        ];
    }


    public function updatedDataRapida($value)
    {
        if ($value === 'hoje') {
            $this->appointment_date = now()->timezone('America/Fortaleza')->format('Y-m-d');
        } elseif ($value === 'ontem') {
            $this->appointment_date = now()->timezone('America/Fortaleza')->subDay()->format('Y-m-d');
        }
    }

    public function updatedTherapyId()
    {
        $this->professional_id = ''; 
        $this->calculateSessions();
    }

    public function updatedPatientId() { $this->calculateSessions(); }
    public function updatedCheckIn() { $this->calculateSessions(); }
    public function updatedCheckOut() { $this->calculateSessions(); }

    // --- Lógica de Negócio ---

    private function calculateSessions()
    {
        if (empty($this->check_in) || empty($this->check_out)) {
            $this->session_number = null;
            return;
        }

        $sessionDuration = 40;

        if (!empty($this->patient_id) && !empty($this->therapy_id)) {
            $patient = Patient::withoutGlobalScopes()->with('agreement')->find($this->patient_id);
            $therapy = Therapy::find($this->therapy_id);

            if ($patient && $therapy) {
                $isHumana = $patient->agreement && $patient->agreement->name === 'Humana';
                $isAba = $therapy->name === 'ABA';

                if ($isHumana) {
                    $sessionDuration = 40;
                } else if ($isAba) {
                    $sessionDuration = 60;
                } else {
                    $sessionDuration = 40;
                }
            }
        }

        $checkInTime = \DateTime::createFromFormat('H:i', $this->check_in);
        $checkOutTime = \DateTime::createFromFormat('H:i', $this->check_out);

        if ($checkInTime && $checkOutTime && $checkOutTime > $checkInTime) {
            $interval = $checkOutTime->diff($checkInTime);
            $minutes = ($interval->h * 60) + $interval->i;
            
            $this->session_number = (int) max(1, round($minutes / $sessionDuration));
        } else {
            $this->session_number = 0;
        }
    }

    private function performSave()
    {
        $this->validate();

        return Appointment::create([
            'patient_id' => $this->patient_id,
            'therapy_id' => $this->therapy_id,
            'service_type_id' => $this->service_type_id,
            'professional_id' => $this->professional_id,
            'appointment_date' => $this->appointment_date,
            'check_in' => $this->check_in,
            'check_out' => $this->check_out,
            'session_number' => $this->session_number,
        ]);
    }

    public function save()
    {
        $this->performSave();
        session()->flash('message', 'Consulta registrada com sucesso!');
        return redirect()->route('terapias-realizadas.index');
    }

    public function saveAndCreateAnother()
    {
        $this->performSave();
        session()->flash('message', 'Consulta registrada com sucesso!');
        
        $this->reset([
            'patient_id', 'therapy_id', 'service_type_id', 
            'professional_id', 'check_in', 'check_out', 'session_number'
        ]);
        
        $this->resetValidation();
    }

    public function render()
    {
        $allowedUnitIds = auth()->user()->getAllowedUnitIds();

        $patientsQuery = Patient::withoutGlobalScopes()->orderBy('name');
        
        $professionalsQuery = Professional::orderBy('name');

        if ($allowedUnitIds !== null) {
            $patientsQuery->whereIn('unit_id', $allowedUnitIds);
            $professionalsQuery->whereHas('units', function($q) use ($allowedUnitIds) {
                $q->whereIn('units.id', $allowedUnitIds);
            });
        }

        if (!empty($this->therapy_id)) {
            $professionalsQuery->whereHas('therapies', function($q) {
                $q->where('therapies.id', $this->therapy_id);
            });
        } else {
            $professionalsQuery->where('id', '<', 0); 
        }

        return view('livewire.terapias-realizadas.create', [
            'patients' => $patientsQuery->get(),
            'therapies' => Therapy::orderBy('name')->get(),
            'serviceTypes' => ServiceType::orderBy('name')->get(),
            'professionals' => $professionalsQuery->get(),
        ]);
    }
}