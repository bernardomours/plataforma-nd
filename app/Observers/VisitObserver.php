<?php

namespace App\Observers;

use App\Models\Visit;
use App\Models\Appointment;
use App\Enums\VisitType;
use App\Enums\VisitStatus;
use Illuminate\Support\Facades\DB;

class VisitObserver
{
    public function creating(Visit $visit): void
    {
        if (empty($visit->therapy_id)) {
            $ultima = Appointment::where('patient_id', $visit->patient_id)
                ->whereHas('therapy', fn($q) => $q->whereIn('name', ['ABA', 'DENVER']))
                ->latest('appointment_date')
                ->first();

            if ($ultima) $visit->therapy_id = $ultima->therapy_id;
        }
    }

    public function saved(Visit $visit): void
    {
        // Usa o Enum Oficial
        if ($visit->isDirty('status') && $visit->status === VisitStatus::Completed && $visit->happened_at) {
            
            $diasAcumulados = Appointment::where('patient_id', $visit->patient_id)
                ->where('service_type_id', $visit->service_type_id)
                ->when($visit->therapy_id, fn($q) => $q->where('therapy_id', $visit->therapy_id))
                ->whereDate('appointment_date', '>', $visit->happened_at)
                ->distinct(DB::raw('DATE(appointment_date)'))
                ->count(DB::raw('DATE(appointment_date)'));

            if ($visit->type === VisitType::Coordination && $diasAcumulados >= 10) {
                $this->gerarPendente($visit, VisitType::Coordination->value);
            }

            if ($visit->type === VisitType::Supervision && $diasAcumulados >= 20) {
                $this->gerarPendente($visit, VisitType::Supervision->value);
            }
        }
    }

    private function gerarPendente(Visit $visit, string $tipo): void
    {
        $existe = Visit::where('patient_id', $visit->patient_id)
            ->where('service_type_id', $visit->service_type_id)
            ->where('type', $tipo)
            ->where('status', VisitStatus::Pending->value)
            ->when($visit->therapy_id, fn($q) => $q->where('therapy_id', $visit->therapy_id)) 
            ->exists();

        if (!$existe) {
            Visit::create([
                'patient_id' => $visit->patient_id,
                'service_type_id' => $visit->service_type_id,
                'professional_id' => $visit->professional_id,
                'type' => $tipo,
                'status' => VisitStatus::Pending->value,
                'therapy_id' => $visit->therapy_id,
                'notes' => 'Gerado automaticamente pelo acúmulo de dias passados.'
            ]);
        }
    }
}