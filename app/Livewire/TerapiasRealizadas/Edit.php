<?php

namespace App\Livewire\TerapiasRealizadas;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Therapy;
use App\Models\ServiceType;
use App\Models\Professional;

#[Layout('layouts.app')]
class Edit extends Component
{
    public $appointmentId;
    
    // Propriedades do Formulário
    public $patient_id;
    public $therapy_id;
    public $service_type_id;
    public $professional_id;
    public $data_rapida = 'outro'; // Padrão 'outro' para exibir a data salva
    public $appointment_date;
    public $check_in;
    public $check_out;
    public $session_number;

    public function mount($id)
    {
        $appointment = Appointment::findOrFail($id);
        $this->appointmentId = $appointment->id;

        // SEGURANÇA: Valida se o agendamento pertence a uma unidade permitida para o usuário
        $allowedUnitIds = auth()->user()->getAllowedUnitIds();
        if ($allowedUnitIds !== null) {
            // Verifica a unidade através do paciente vinculado
            $patientUnitId = Patient::withoutGlobalScopes()->where('id', $appointment->patient_id)->value('unit_id');
            if (!in_array($patientUnitId, $allowedUnitIds)) {
                abort(403, 'Acesso não autorizado a esta unidade.');
            }
        }

        // Inicialização das propriedades
        $this->patient_id = $appointment->patient_id;
        $this->therapy_id = $appointment->therapy_id;
        $this->service_type_id = $appointment->service_type_id;
        $this->professional_id = $appointment->professional_id;
        $this->appointment_date = $appointment->appointment_date ? \Carbon\Carbon::parse($appointment->appointment_date)->format('Y-m-d') : null;
        
        // Formata os tempos para o padrão H:i (removendo os segundos do banco)
        $this->check_in = $appointment->check_in ? \Carbon\Carbon::parse($appointment->check_in)->format('H:i') : null;
        $this->check_out = $appointment->check_out ? \Carbon\Carbon::parse($appointment->check_out)->format('H:i') : null;
        $this->session_number = $appointment->session_number;
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

                $sessionDuration = $isHumana ? 40 : ($isAba ? 60 : 40);
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

    public function save()
    {
        $this->validate();

        $appointment = Appointment::findOrFail($this->appointmentId);
        
        $appointment->update([
            'patient_id' => $this->patient_id,
            'therapy_id' => $this->therapy_id,
            'service_type_id' => $this->service_type_id,
            'professional_id' => $this->professional_id,
            'appointment_date' => $this->appointment_date,
            'check_in' => $this->check_in,
            'check_out' => $this->check_out,
            'session_number' => $this->session_number,
        ]);

        session()->flash('message', 'Consulta atualizada com sucesso!');
        return redirect()->route('terapias-realizadas.index');
    }

    public function render()
    {
        $allowedUnitIds = auth()->user()->getAllowedUnitIds();

        // 1. Inicia as queries
        $patientsQuery = Patient::withoutGlobalScopes()->orderBy('name');
        $professionalsQuery = Professional::orderBy('name');

        // 2. Aplica as regras de segurança (Multi-tenancy)
        if ($allowedUnitIds !== null) {
            $patientsQuery->whereIn('unit_id', $allowedUnitIds);
            $professionalsQuery->whereHas('units', function($q) use ($allowedUnitIds) {
                $q->whereIn('units.id', $allowedUnitIds);
            });
        }

        // 3. Filtra os profissionais com base na terapia selecionada
        if (!empty($this->therapy_id)) {
            $professionalsQuery->whereHas('therapies', function($q) {
                $q->where('therapies.id', $this->therapy_id);
            });
        } else {
            // Se não houver terapia, não carrega profissionais
            $professionalsQuery->where('id', '<', 0); 
        }

        // 4. Retorna a view com todas as variáveis obrigatórias
        return view('livewire.terapias-realizadas.edit', [
            'patients' => $patientsQuery->get(),
            'therapies' => Therapy::orderBy('name')->get(),
            'serviceTypes' => ServiceType::orderBy('name')->get(),
            'professionals' => $professionalsQuery->get(),
        ]);
    }
}