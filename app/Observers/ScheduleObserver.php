<?php

namespace App\Observers;

use App\Models\Patient;
use App\Models\RequestedService;
use App\Models\Schedule;
use App\Services\PlannedSessionsFromSchedule;
use Carbon\Carbon;

/**
 * Mantém a CH Planejada em dia sempre que a agenda do paciente muda — criar,
 * editar ou excluir um horário —, sem depender de alguém lembrar de rodar
 * `ch:recalcular-planejada` manualmente.
 *
 * Só toca na competência VIGENTE em diante (mesmo corte do comando). Mês
 * fechado continua congelado: não precisa de nenhuma lógica para "fechar o
 * mês" porque, quando o calendário vira, a competência anterior simplesmente
 * sai do filtro `month_year >= início do mês atual` e o último valor gravado
 * fica definitivo — o comportamento que já existia, só que automático agora
 * para o mês corrente.
 *
 * NÃO sobrescreve CH marcada como Manual (`planned_from_schedule = false` com
 * um valor já preenchido). Sem essa proteção, criar um horário para OUTRO
 * paciente nem entraria aqui, mas editar/excluir um bloco do MESMO paciente
 * apagaria uma exceção que uma coordenadora digitou de propósito (convênio
 * autorizou menos sessões que a agenda comporta, por exemplo) só porque a
 * agenda dele mudou em outro ponto. Mesma proteção aplicada ao comando.
 */
class ScheduleObserver
{
    private const CAMPOS_RELEVANTES = [
        'patient_id', 'therapy_id', 'service_type_id',
        'day_of_week', 'start_time', 'end_time', 'is_blocked',
    ];

    public function created(Schedule $schedule): void
    {
        $this->recalcularPaciente($schedule->patient_id);
    }

    public function updated(Schedule $schedule): void
    {
        if (! $schedule->wasChanged(self::CAMPOS_RELEVANTES)) {
            return;
        }

        $this->recalcularPaciente($schedule->patient_id);

        // Trocar o paciente do bloco tira sessão de um e dá a outro: os dois
        // precisam recalcular, não só o dono atual.
        $pacienteAnterior = $schedule->getOriginal('patient_id');

        if ($pacienteAnterior && $pacienteAnterior !== $schedule->patient_id) {
            $this->recalcularPaciente($pacienteAnterior);
        }
    }

    public function deleted(Schedule $schedule): void
    {
        $this->recalcularPaciente($schedule->patient_id);
    }

    private function recalcularPaciente(?int $patientId): void
    {
        if (! $patientId) {
            return;
        }

        $paciente = Patient::withoutGlobalScopes()->with('agreement')->find($patientId);

        if (! $paciente) {
            return;
        }

        $inicioDoMesAtual = Carbon::now()->startOfMonth();

        $registros = RequestedService::withoutGlobalScopes()
            ->where('patient_id', $patientId)
            ->whereDate('month_year', '>=', $inicioDoMesAtual->toDateString())
            ->get();

        if ($registros->isEmpty()) {
            return;
        }

        $calc = app(PlannedSessionsFromSchedule::class);

        // Um paciente costuma ter CH aberta em 1 ou 2 competências (a atual e,
        // às vezes, a seguinte já pré-cadastrada) — agrupar evita recalcular a
        // agenda inteira do paciente uma vez por linha de requisição.
        $porCompetencia = $registros->groupBy(
            fn ($registro) => Carbon::parse($registro->month_year)->format('Y-m')
        );

        foreach ($porCompetencia as $competenciaChave => $linhas) {
            $competencia = Carbon::createFromFormat('Y-m', $competenciaChave)->startOfMonth();
            $agenda = $calc->paraPaciente($paciente, $competencia);

            foreach ($linhas as $registro) {
                $eraManual = ! $registro->planned_from_schedule && $registro->planned_sessions !== null;

                if ($eraManual) {
                    continue;
                }

                $derivado = $agenda[$registro->therapy_id . ':' . $registro->service_type_id] ?? null;

                $novoMensal  = $derivado['mensal']  ?? null;
                $novoSemanal = $derivado['semanal'] ?? null;

                if ((int) $registro->planned_sessions === (int) $novoMensal
                    && (float) $registro->planned_hours === (float) $novoSemanal) {
                    continue;
                }

                $registro->forceFill([
                    'planned_sessions'      => $novoMensal,
                    'planned_hours'         => $novoSemanal,
                    'planned_from_schedule' => $derivado !== null,
                ])->save();
            }
        }
    }
}
