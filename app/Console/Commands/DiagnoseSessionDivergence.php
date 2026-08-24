<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DiagnoseSessionDivergence extends Command
{
    protected $signature = 'ch:conferir-divergencia {--mes= : Competência YYYY-MM. Padrão: mês corrente.}';

    protected $description = 'Decompõe a diferença de sessões entre Relatórios Gerais e CH Solicitada';

    public function handle(): int
    {
        $competencia = $this->option('mes')
            ? Carbon::createFromFormat('Y-m', $this->option('mes'))
            : now();

        $ano = $competencia->year;
        $mes = $competencia->month;

        $sub = "SELECT patient_id, therapy_id, service_type_id,
                       YEAR(appointment_date) ano, MONTH(appointment_date) mes,
                       SUM(COALESCE(session_number,0)) sessoes, COUNT(*) atendimentos
                FROM appointments
                WHERE check_in IS NOT NULL AND check_out IS NOT NULL AND check_out > check_in
                  AND YEAR(appointment_date) = ? AND MONTH(appointment_date) = ?
                GROUP BY patient_id, therapy_id, service_type_id, ano, mes";

        $relatorio = DB::selectOne(
            'SELECT COUNT(*) atend, COALESCE(SUM(session_number),0) sess FROM appointments
             WHERE YEAR(appointment_date) = ? AND MONTH(appointment_date) = ?',
            [$ano, $mes]
        );

        // A tela agrupa por paciente + terapia + tipo + competência, então cada combinação
        // conta uma vez — mesmo quando há requisições complementares.
        $ch = DB::selectOne("
            SELECT COALESCE(SUM(ap.atendimentos),0) atend, COALESCE(SUM(ap.sessoes),0) sess
            FROM ({$sub}) ap
            WHERE EXISTS (
                SELECT 1 FROM requested_services rs
                JOIN patients p ON p.id = rs.patient_id
                WHERE rs.patient_id = ap.patient_id AND rs.therapy_id = ap.therapy_id
                  AND rs.service_type_id = ap.service_type_id
                  AND YEAR(rs.month_year) = ? AND MONTH(rs.month_year) = ?)",
            [$ano, $mes, $ano, $mes]
        );

        $this->info("Competência {$competencia->format('m/Y')}");
        $this->newLine();
        $this->table(['Tela', 'Atendimentos', 'Sessões'], [
            ['Relatórios Gerais', number_format($relatorio->atend, 0, ',', '.'), number_format($relatorio->sess, 0, ',', '.')],
            ['CH Solicitada', number_format($ch->atend, 0, ',', '.'), number_format($ch->sess, 0, ',', '.')],
            ['Diferença', sprintf('%+d', $ch->atend - $relatorio->atend), sprintf('%+d', $ch->sess - $relatorio->sess)],
        ]);

        $invalidos = DB::selectOne(
            'SELECT COUNT(*) atend, COALESCE(SUM(session_number),0) sess FROM appointments
             WHERE YEAR(appointment_date)=? AND MONTH(appointment_date)=?
               AND (check_in IS NULL OR check_out IS NULL OR check_out <= check_in)',
            [$ano, $mes]
        );

        $semCh = DB::selectOne(
            'SELECT COUNT(*) atend, COALESCE(SUM(a.session_number),0) sess FROM appointments a
             WHERE YEAR(a.appointment_date)=? AND MONTH(a.appointment_date)=?
               AND a.check_in IS NOT NULL AND a.check_out IS NOT NULL AND a.check_out > a.check_in
               AND NOT EXISTS (SELECT 1 FROM requested_services rs
                    WHERE rs.patient_id=a.patient_id AND rs.therapy_id=a.therapy_id
                      AND rs.service_type_id=a.service_type_id
                      AND YEAR(rs.month_year)=YEAR(a.appointment_date)
                      AND MONTH(rs.month_year)=MONTH(a.appointment_date))',
            [$ano, $mes]
        );

        $duplicados = DB::selectOne("
            SELECT COALESCE(SUM((g.n-1) * ap.atendimentos),0) atend,
                   COALESCE(SUM((g.n-1) * ap.sessoes),0) sess
            FROM (SELECT patient_id, therapy_id, service_type_id, COUNT(*) n
                  FROM requested_services
                  WHERE YEAR(month_year)=? AND MONTH(month_year)=?
                  GROUP BY patient_id, therapy_id, service_type_id
                  HAVING COUNT(*) > 1) g
            JOIN ({$sub}) ap
              ON ap.patient_id=g.patient_id AND ap.therapy_id=g.therapy_id
             AND ap.service_type_id=g.service_type_id",
            [$ano, $mes, $ano, $mes]
        );

        $comSaida = DB::selectOne(
            'SELECT COUNT(*) atend, COALESCE(SUM(a.session_number),0) sess
             FROM appointments a JOIN patients p ON p.id=a.patient_id
             WHERE YEAR(a.appointment_date)=? AND MONTH(a.appointment_date)=? AND p.deleted_at IS NOT NULL',
            [$ano, $mes]
        );

        $this->newLine();
        $this->line('<options=bold>Decomposição</> (sinal em relação ao Relatório Geral)');
        $this->table(['Causa', 'Atendimentos', 'Sessões'], [
            ['CH descarta check_out nulo ou invertido', sprintf('%+d', -$invalidos->atend), sprintf('%+d', -$invalidos->sess)],
            ['Atendimento sem CH cadastrada', sprintf('%+d', -$semCh->atend), sprintf('%+d', -$semCh->sess)],
            ['(corrigido) requisições complementares', '0', '0'],
            ['(corrigido) paciente com saída registrada', '0', '0'],
        ]);

        $previstoAtend = $relatorio->atend - $invalidos->atend - $semCh->atend;
        $previstoSess  = $relatorio->sess - $invalidos->sess - $semCh->sess;

        $this->newLine();
        $this->line("Previsto para a CH .. {$previstoAtend} atend / {$previstoSess} sessões");
        $this->line("Real da CH .......... {$ch->atend} atend / {$ch->sess} sessões");
        $this->line(sprintf('Resíduo ............. %+d atend / %+d sessões', $ch->atend - $previstoAtend, $ch->sess - $previstoSess));

        if ($duplicados->sess > 0) {
            $this->newLine();
            $this->line(sprintf(
                'Requisições complementares nesta competência: sem o agrupamento, a tela inflaria %d sessões.',
                $duplicados->sess
            ));
        }

        return self::SUCCESS;
    }
}
