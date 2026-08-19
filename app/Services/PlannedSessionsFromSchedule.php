<?php

namespace App\Services;

use App\Models\Holiday;
use App\Models\Patient;
use App\Models\Schedule;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Deriva a carga horária PLANEJADA de um paciente a partir da agenda semanal dele.
 *
 * A agenda (tabela schedules) é uma grade semanal fixa: "toda segunda das 14h20 às 17h20,
 * ABA na clínica". Para chegar à quantidade de sessões de um MÊS são três passos:
 *
 *   1. Converter cada bloco de tempo em sessões, pela regra de duração do convênio/terapia
 *      (um bloco não é uma sessão — há blocos de 40, 120, 180 e até 240 minutos na base);
 *   2. Contar quantas vezes aquele dia da semana ocorre no mês, descontando feriados;
 *   3. Multiplicar um pelo outro.
 *
 * O passo 2 é o que dá precisão: setembro/2026 tem 5 terças e 4 segundas, outubro tem 4
 * terças e 5 sextas. Multiplicar tudo por 4 erra para mais ou para menos conforme o mês.
 */
class PlannedSessionsFromSchedule
{
    /**
     * Mapeia o day_of_week da tabela schedules para o índice do Carbon (0=domingo).
     * Os valores gravados são minúsculos e sem acento: segunda, terca, quarta, quinta, sexta.
     * As variantes acentuadas e o fim de semana entram por robustez, caso surjam.
     */
    private const DIAS = [
        'domingo' => Carbon::SUNDAY,
        'segunda' => Carbon::MONDAY,
        'terca'   => Carbon::TUESDAY,
        'terça'   => Carbon::TUESDAY,
        'quarta'  => Carbon::WEDNESDAY,
        'quinta'  => Carbon::THURSDAY,
        'sexta'   => Carbon::FRIDAY,
        'sabado'  => Carbon::SATURDAY,
        'sábado'  => Carbon::SATURDAY,
    ];

    /**
     * Duração de uma sessão, em minutos.
     *
     * Regra do negócio, confirmada pela gerência e conferida contra 32 mil atendimentos:
     *   - Humana (qualquer terapia) ......... 40 min
     *   - ABA em qualquer outro convênio .... 60 min
     *   - Demais terapias ................... 40 min
     *
     * É a MESMA regra de TerapiasRealizadas::calculateSessions(). Está aqui como método
     * público e estático justamente para que aquelas telas possam passar a apontar para
     * cá no futuro, em vez de manter uma terceira cópia que pode divergir.
     */
    public static function duracaoDaSessao(?string $convenio, ?string $terapia): int
    {
        if ($convenio === 'Humana') {
            return 40;
        }

        return $terapia === 'ABA' ? 60 : 40;
    }

    /**
     * Quantas sessões cabem num bloco de agenda.
     * O round() com mínimo de 1 replica o comportamento de calculateSessions(): um bloco
     * de 100 min com sessão de 40 conta como 3, não 2.
     */
    public static function sessoesNoBloco(int $minutos, int $duracaoSessao): int
    {
        if ($minutos <= 0 || $duracaoSessao <= 0) {
            return 0;
        }

        return (int) max(1, round($minutos / $duracaoSessao));
    }

    /**
     * Ocorrências de cada dia da semana no mês, já descontados os feriados.
     *
     * @return array<int,int> índice = dia da semana do Carbon, valor = quantidade
     */
    public function ocorrenciasPorDiaDaSemana(CarbonInterface $competencia): array
    {
        $inicio = $competencia->copy()->startOfMonth();
        $fim    = $competencia->copy()->endOfMonth();

        // Feriados e recessos cadastrados. Com a tabela vazia isto é um no-op — passa a
        // afetar o cálculo automaticamente assim que as datas forem cadastradas.
        $feriados = Holiday::whereBetween('date', [$inicio->toDateString(), $fim->toDateString()])
            ->pluck('date')
            ->map(fn ($data) => Carbon::parse($data)->toDateString())
            ->flip();

        $contagem = array_fill(0, 7, 0);

        for ($dia = $inicio->copy(); $dia->lte($fim); $dia->addDay()) {
            if ($feriados->has($dia->toDateString())) {
                continue;
            }

            $contagem[$dia->dayOfWeek]++;
        }

        return $contagem;
    }

    /**
     * Sessões planejadas do paciente no mês, agrupadas por terapia + tipo de atendimento —
     * exatamente a granularidade de requested_services.
     *
     * @return array<string,array{therapy_id:int,service_type_id:int,semanal:int,mensal:int,blocos:array}>
     *         chave no formato "therapy_id:service_type_id"
     */
    public function paraPaciente(Patient $patient, CarbonInterface $competencia): array
    {
        $ocorrencias = $this->ocorrenciasPorDiaDaSemana($competencia);
        $convenio    = $patient->agreement?->name;

        $blocos = Schedule::query()
            ->with(['therapy', 'serviceType'])
            ->where('patient_id', $patient->id)
            ->where('is_blocked', false)
            ->whereNotNull('therapy_id')
            ->whereNotNull('service_type_id')
            // Descarta blocos com horário invertido (há 1 na base, com -360 min), que
            // gerariam sessões negativas — mesmo tipo de erro já encontrado em appointments.
            ->whereColumn('end_time', '>', 'start_time')
            ->get();

        $resultado = [];

        foreach ($blocos as $bloco) {
            $diaSemana = self::DIAS[mb_strtolower(trim((string) $bloco->day_of_week))] ?? null;

            if ($diaSemana === null) {
                continue;
            }

            $minutos  = $this->minutosDoBloco($bloco);
            $duracao  = self::duracaoDaSessao($convenio, $bloco->therapy?->name);
            $sessoes  = self::sessoesNoBloco($minutos, $duracao);

            if ($sessoes === 0) {
                continue;
            }

            $chave = $bloco->therapy_id . ':' . $bloco->service_type_id;

            $resultado[$chave] ??= [
                'therapy_id'      => (int) $bloco->therapy_id,
                'service_type_id' => (int) $bloco->service_type_id,
                'therapy'         => $bloco->therapy?->name,
                'service_type'    => $bloco->serviceType?->name,
                'semanal'         => 0,
                'mensal'          => 0,
                'blocos'          => [],
            ];

            $vezesNoMes = $ocorrencias[$diaSemana] ?? 0;

            $resultado[$chave]['semanal'] += $sessoes;
            $resultado[$chave]['mensal']  += $sessoes * $vezesNoMes;

            // Guardado para a tela poder explicar de onde saiu o número.
            $resultado[$chave]['blocos'][] = [
                'dia'         => $bloco->day_of_week,
                'inicio'      => substr((string) $bloco->start_time, 0, 5),
                'fim'         => substr((string) $bloco->end_time, 0, 5),
                'minutos'     => $minutos,
                'sessoes'     => $sessoes,
                'ocorrencias' => $vezesNoMes,
            ];
        }

        return $resultado;
    }

    /**
     * Mesmo cálculo, restrito a uma combinação terapia + tipo de atendimento.
     * Retorna null quando o paciente não tem essa combinação na agenda.
     */
    public function paraCombinacao(Patient $patient, $therapyId, $serviceTypeId, CarbonInterface $competencia): ?array
    {
        if (! $therapyId || ! $serviceTypeId) {
            return null;
        }

        return $this->paraPaciente($patient, $competencia)[$therapyId . ':' . $serviceTypeId] ?? null;
    }

    /** O paciente tem alguma agenda cadastrada? Usado para avisar na tela. */
    public function pacienteTemAgenda(Patient $patient): bool
    {
        return Schedule::where('patient_id', $patient->id)
            ->where('is_blocked', false)
            ->whereNotNull('therapy_id')
            ->exists();
    }

    /**
     * Duração do bloco em minutos, calculada dos campos TIME sem passar por datas.
     */
    private function minutosDoBloco(Schedule $bloco): int
    {
        $converte = function ($hora): int {
            [$h, $m] = array_map('intval', explode(':', substr((string) $hora, 0, 5)));

            return $h * 60 + $m;
        };

        return $converte($bloco->end_time) - $converte($bloco->start_time);
    }
}
