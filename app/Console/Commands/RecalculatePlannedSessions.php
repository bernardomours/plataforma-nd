<?php

namespace App\Console\Commands;

use App\Models\Patient;
use App\Models\RequestedService;
use App\Services\PlannedSessionsFromSchedule;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RecalculatePlannedSessions extends Command
{
    protected $signature = 'ch:recalcular-planejada
                            {--desde= : Competência inicial no formato YYYY-MM. Padrão: mês corrente.}
                            {--fix : Aplica as alterações. Sem esta flag apenas simula.}';

    protected $description = 'Regrava a CH planejada a partir da agenda, do mês informado em diante';

    public function handle(PlannedSessionsFromSchedule $calc): int
    {
        $desde = $this->option('desde')
            ? Carbon::createFromFormat('Y-m', $this->option('desde'))->startOfMonth()
            : now()->startOfMonth();

        $aplicar = (bool) $this->option('fix');

        $query = RequestedService::query()->whereDate('month_year', '>=', $desde->toDateString());
        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info("Nenhum registro a partir de {$desde->format('m/Y')}.");

            return self::SUCCESS;
        }

        $this->info("Competência a partir de {$desde->format('m/Y')} — {$total} registro(s).");
        $this->line('Meses anteriores não são tocados: competência fechada permanece congelada.');
        $this->newLine();

        $barra = $this->output->createProgressBar($total);
        $barra->start();

        $derivados = 0;
        $limpos    = 0;
        $iguais    = 0;
        $manuais   = 0;
        $cache     = [];

        $query->orderBy('id')->chunkById(200, function ($registros) use (
            $calc, $aplicar, $barra, &$derivados, &$limpos, &$iguais, &$manuais, &$cache
        ) {
            foreach ($registros as $r) {
                $barra->advance();

                $competencia = Carbon::parse($r->month_year);
                $chave = $r->patient_id . ':' . $competencia->format('Y-m');

                if (! array_key_exists($chave, $cache)) {
                    $paciente = Patient::withoutGlobalScopes()->with('agreement')->find($r->patient_id);
                    $cache[$chave] = $paciente ? $calc->paraPaciente($paciente, $competencia) : [];
                }

                // Protege exceção manual: linha sem planned_from_schedule mas com um
                // valor já preenchido foi digitada de propósito (ex.: convênio autorizou
                // menos sessões do que a agenda comporta) — --fix não deve apagar isso
                // silenciosamente. Mesma regra do ScheduleObserver.
                if (! $r->planned_from_schedule && $r->planned_sessions !== null) {
                    $manuais++;

                    continue;
                }

                $agenda = $cache[$chave][$r->therapy_id . ':' . $r->service_type_id] ?? null;

                $novoMensal  = $agenda['mensal'] ?? null;
                $novoSemanal = $agenda['semanal'] ?? null;

                if ((int) $r->planned_sessions === (int) $novoMensal
                    && (float) $r->planned_hours === (float) $novoSemanal) {
                    $iguais++;

                    continue;
                }

                $agenda ? $derivados++ : $limpos++;

                if ($aplicar) {
                    $r->forceFill([
                        'planned_sessions'      => $novoMensal,
                        'planned_hours'         => $novoSemanal,
                        'planned_from_schedule' => $agenda !== null,
                    ])->save();
                }
            }
        });

        $barra->finish();
        $this->newLine(2);

        $this->line("Recalculados pela agenda ..... <fg=green>{$derivados}</>");
        $this->line("Limpos (sem agenda) .......... <fg=yellow>{$limpos}</>");
        $this->line("Já estavam corretos .......... {$iguais}");
        $this->line("Manuais, preservados ......... <fg=cyan>{$manuais}</>");

        if (! $aplicar) {
            $this->newLine();
            $this->warn('Simulação — nada foi alterado.');
            $this->line('Para aplicar:  php artisan ch:recalcular-planejada --fix');
        }

        return self::SUCCESS;
    }
}
