<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Falta;
use App\Models\Schedule;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Resolve, pra uma data específica, o status de cada horário fixo da grade
 * (Schedule): 'pendente' (nada aconteceu ainda), 'realizado' (virou Appointment) ou
 * 'falta' (paciente não veio, com motivo registrado). Único ponto de leitura desse
 * cruzamento — usado pela Agenda Diária da Recepção e por Minha Agenda do profissional,
 * pra nunca divergir entre as duas telas.
 *
 * Sempre em lote (2 queries fixas, não 1 por horário) — pensado pra tela da recepção,
 * que pode listar a grade inteira da unidade num dia.
 */
class StatusAgendaDoDia
{
    public const PENDENTE = 'pendente';
    public const REALIZADO = 'realizado';
    public const FALTA = 'falta';

    /**
     * @param Collection<int, Schedule> $schedules
     * @return Collection<int, object{schedule: Schedule, status: string, appointment: ?Appointment, falta: ?Falta}>
     */
    public function resolver(Collection $schedules, CarbonInterface $data): Collection
    {
        $ids = $schedules->pluck('id');

        if ($ids->isEmpty()) {
            return collect();
        }

        $appointmentsPorSchedule = Appointment::with(['professional' => fn ($q) => $q->withTrashed()])
            ->whereIn('schedule_id', $ids)
            ->whereDate('appointment_date', $data)
            ->get()
            ->keyBy('schedule_id');

        $faltasPorSchedule = Falta::with('registeredBy')
            ->whereIn('schedule_id', $ids)
            ->whereDate('date', $data)
            ->get()
            ->keyBy('schedule_id');

        return $schedules->map(function (Schedule $schedule) use ($appointmentsPorSchedule, $faltasPorSchedule) {
            $falta = $faltasPorSchedule->get($schedule->id);
            $appointment = $appointmentsPorSchedule->get($schedule->id);

            $status = self::PENDENTE;
            if ($falta) {
                $status = self::FALTA;
            } elseif ($appointment) {
                $status = self::REALIZADO;
            }

            return (object) [
                'schedule' => $schedule,
                'status' => $status,
                'appointment' => $appointment,
                'falta' => $falta,
            ];
        });
    }

    /**
     * Impede duplicidade em corrida (duplo clique / duas abas): confere na hora de
     * salvar, não só na exibição, se o horário já foi resolvido nesta data.
     */
    public function jaResolvido(Schedule $schedule, CarbonInterface $data): bool
    {
        return Appointment::where('schedule_id', $schedule->id)->whereDate('appointment_date', $data)->exists()
            || Falta::where('schedule_id', $schedule->id)->whereDate('date', $data)->exists();
    }
}
