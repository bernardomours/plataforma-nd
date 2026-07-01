<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Models\PatientService;
use App\Models\Visit;
use App\Enums\VisitType;
use App\Enums\VisitStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AppointmentObserver
{
    public function created(Appointment $appointment): void
    {
        $this->checkAndCreateVisit($appointment);
    }

    public function updated(Appointment $appointment): void
    {
        if ($appointment->isDirty(['appointment_date', 'service_type_id', 'therapy_id'])) {
            $this->checkAndCreateVisit($appointment);
        }
    }

    protected function checkAndCreateVisit(Appointment $appointment): void
    {
        if (!$appointment->therapy || !in_array(strtoupper($appointment->therapy->name), ['ABA', 'DENVER'])) {
            return;
        }

        $ambienteBusca = $appointment->service_type_id;
        
        if ($ambienteBusca) {
            $patientService = PatientService::firstOrCreate([
                'patient_id' => $appointment->patient_id,
                'service_type_id' => $ambienteBusca,
            ]);
        } else {
            $patientService = PatientService::where('patient_id', $appointment->patient_id)->first();
            if (!$patientService) return;
        }

        // Usando o Enum Oficial (Coordination)
        $this->processThreshold(
            $patientService, 
            VisitType::Coordination->value, 
            10, 
            $patientService->coordinator_id, 
            $appointment->therapy_id, 
            $appointment->therapy->name
        );

        // Usando o Enum Oficial (Supervision)
        $this->processThreshold(
            $patientService, 
            VisitType::Supervision->value, 
            20, 
            $patientService->supervisor_id, 
            $appointment->therapy_id, 
            $appointment->therapy->name
        );
    }

    protected function processThreshold(PatientService $patientService, string $type, int $threshold, ?int $professionalId, int $therapyId, string $therapyName): void
    {
        $lastCompleted = Visit::where('patient_id', $patientService->patient_id)
            ->where(fn($q) => $q->where('service_type_id', $patientService->service_type_id)->orWhereNull('service_type_id'))
            ->where('type', $type) 
            ->where('status', VisitStatus::Completed->value) // Usando o Enum de Status
            ->where('therapy_id', $therapyId)
            ->latest('happened_at')
            ->first();

        $query = Appointment::where('patient_id', $patientService->patient_id)
            ->where('service_type_id', $patientService->service_type_id)
            ->where('therapy_id', $therapyId)
            ->where('appointment_date', '<=', Carbon::today());

        if ($lastCompleted && $lastCompleted->happened_at) {
            $query->where('appointment_date', '>', $lastCompleted->happened_at);
        }

        $daysCount = $query->distinct(DB::raw('DATE(appointment_date)'))->count(DB::raw('DATE(appointment_date)'));

        $visitaPendente = Visit::where('patient_id', $patientService->patient_id)
            ->where(fn($q) => $q->where('service_type_id', $patientService->service_type_id)->orWhereNull('service_type_id'))
            ->where('type', $type) 
            ->where('status', VisitStatus::Pending->value) // Usando o Enum de Status
            ->where('therapy_id', $therapyId)
            ->first();

        if ($daysCount >= $threshold) {
            if (!$visitaPendente) {
                Visit::create([
                    'patient_id'      => $patientService->patient_id,
                    'service_type_id' => $patientService->service_type_id,
                    'professional_id' => $professionalId,
                    'type'            => $type,
                    'status'          => VisitStatus::Pending->value, // Usando o Enum de Status
                    'therapy_id'      => $therapyId,
                    'notes'           => "Gerado automaticamente após atingir {$threshold} dias de {$therapyName}.",
                ]);
            }
        } elseif ($visitaPendente) {
            $visitaPendente->delete();
        }
    }
}