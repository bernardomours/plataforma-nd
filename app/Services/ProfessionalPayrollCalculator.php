<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Professional;
use App\Models\ProfessionalPaymentRule;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Apura o valor a repassar por profissional a partir dos atendimentos do mês.
 *
 * As regras de `professional_payment_rules` filtram por terapia, tipo de atendimento e
 * convênio; qualquer um nulo funciona como curinga. Vence a regra mais específica que casar
 * (mais campos preenchidos). Atendimento com `is_glosado` fica fora.
 */
class ProfessionalPayrollCalculator
{
    /** Tipos que o cálculo sabe converter em valor. */
    public const TIPOS_SUPORTADOS = ['por_sessao', 'Por Sessão'];

    public function resumoDoProfissional(Professional $prof, int $ano, int $mes, $therapyId = null): array
    {
        $atendimentos = Appointment::with(['therapy', 'serviceType', 'patient.agreement'])
            ->where('professional_id', $prof->id)
            ->tap($this->noMes($ano, $mes))
            ->whereNotNull('check_in')
            ->where('is_glosado', false)
            ->when($therapyId, fn ($q) => $q->where('therapy_id', $therapyId))
            ->get();

        if ($atendimentos->isEmpty()) {
            return ['sessoes' => 0, 'valor_regra' => 'Sem produção', 'valor_total' => 0];
        }

        $regras = ProfessionalPaymentRule::where('professional_id', $prof->id)->get();

        if ($regras->isEmpty()) {
            return [
                'sessoes'     => $atendimentos->sum('session_number'),
                'valor_regra' => 'Sem Regra Cadastrada',
                'valor_total' => 0,
            ];
        }

        return $this->aplicar($atendimentos, $regras);
    }

    /**
     * Apura o mês inteiro em duas consultas — o loop por profissional do Fechamento
     * seria centenas de idas ao banco.
     *
     * Cada profissional volta com `situacao`: `ok`, `sem_regra` (nenhuma regra cadastrada)
     * ou `incompativel` (tem regra, mas nenhuma casou ou o `payment_type` não é por sessão).
     *
     * `$ateDia` corta o mês num dia — serve para comparar mês corrente incompleto com o
     * mesmo período do mês anterior.
     */
    public function totaisDoMes(int $ano, int $mes, ?int $ateDia = null): array
    {
        $atendimentos = Appointment::query()
            ->join('patients', 'patients.id', '=', 'appointments.patient_id')
            ->tap($this->noMes($ano, $mes, $ateDia, 'appointments.appointment_date'))
            ->whereNotNull('appointments.check_in')
            ->where('appointments.is_glosado', false)
            ->select([
                'appointments.professional_id',
                'appointments.therapy_id',
                'appointments.service_type_id',
                'appointments.session_number',
                DB::raw('COALESCE(appointments.agreement_id, patients.agreement_id) as agreement_id'),
            ])
            ->get();

        $regrasPorProfissional = ProfessionalPaymentRule::all()->groupBy('professional_id');

        $profissionais = Professional::withTrashed()
            ->whereIn('id', $atendimentos->pluck('professional_id')->unique())
            ->get(['id', 'name', 'deleted_at'])
            ->keyBy('id');

        $linhas = collect();

        foreach ($atendimentos->groupBy('professional_id') as $profissionalId => $doProfissional) {
            $regras = $regrasPorProfissional->get($profissionalId);

            $resultado = ($regras && $regras->isNotEmpty())
                ? $this->aplicar($doProfissional, $regras)
                : ['sessoes' => (int) $doProfissional->sum('session_number'), 'valor_total' => 0];

            $linhas->push((object) [
                'id'       => $profissionalId,
                'nome'     => $profissionais[$profissionalId]->name ?? 'Profissional #' . $profissionalId,
                'inativo'  => (bool) ($profissionais[$profissionalId]->deleted_at ?? false),
                'sessoes'  => (int) $resultado['sessoes'],
                'valor'    => (float) $resultado['valor_total'],
                'situacao' => match (true) {
                    ! $regras || $regras->isEmpty() => 'sem_regra',
                    $resultado['valor_total'] <= 0  => 'incompativel',
                    default                         => 'ok',
                },
            ]);
        }

        $glosados = Appointment::query()
            ->tap($this->noMes($ano, $mes, $ateDia))
            ->where('is_glosado', true)
            ->selectRaw('COUNT(*) as atendimentos, COALESCE(SUM(session_number), 0) as sessoes')
            ->first();

        return [
            'valor_total'      => (float) $linhas->sum('valor'),
            'sessoes'          => (int) $atendimentos->sum('session_number'),
            'atendimentos'     => $atendimentos->count(),
            'profissionais'    => $linhas->count(),
            'por_profissional' => $linhas->sortByDesc('valor')->values(),
            'glosados'         => [
                'atendimentos' => (int) ($glosados->atendimentos ?? 0),
                'sessoes'      => (int) ($glosados->sessoes ?? 0),
            ],
        ];
    }

    /** Regras cujo `payment_type` o cálculo não sabe converter em valor. */
    public function regrasComTipoNaoSuportado(): Collection
    {
        return ProfessionalPaymentRule::with('professional')
            ->whereNotIn('payment_type', self::TIPOS_SUPORTADOS)
            ->get();
    }

    /**
     * Aplica as regras aos atendimentos. Ordena por especificidade e usa a primeira que casa.
     */
    private function aplicar(Collection $atendimentos, Collection $regras): array
    {
        $ordenadas = $regras->sortByDesc(function ($r) {
            return (int) ! is_null($r->therapy_id)
                 + (int) ! is_null($r->service_type_id)
                 + (int) ! is_null($r->agreement_id);
        });

        $sessoes = 0;
        $valor = 0.0;
        $etiquetas = [];

        foreach ($atendimentos as $atendimento) {
            $qtd = $atendimento->session_number ?? 1;
            $sessoes += $qtd;

            $convenioId = $atendimento->agreement_id
                ?? $atendimento->patient->agreement_id
                ?? null;

            $regra = $ordenadas->first(function ($r) use ($atendimento, $convenioId) {
                return (is_null($r->therapy_id)      || $r->therapy_id == $atendimento->therapy_id)
                    && (is_null($r->service_type_id) || $r->service_type_id == $atendimento->service_type_id)
                    && (is_null($r->agreement_id)    || $r->agreement_id == $convenioId);
            });

            if (! $regra || ! in_array($regra->payment_type, self::TIPOS_SUPORTADOS, true)) {
                continue;
            }

            $valor += $qtd * $regra->amount;
            $etiquetas[$this->etiqueta($regra)] = $regra->amount;
        }

        $textos = [];
        foreach ($etiquetas as $nome => $v) {
            $textos[] = $nome . ' (R$ ' . number_format($v, 2, ',', '.') . ')';
        }

        return [
            'sessoes'     => $sessoes,
            'valor_regra' => empty($textos) ? 'Regras Incompatíveis' : implode(' | ', $textos),
            'valor_total' => $valor,
        ];
    }

    private function etiqueta(ProfessionalPaymentRule $regra): string
    {
        $partes = [];

        if ($regra->agreement_id)    $partes[] = $regra->agreement->name ?? 'Convênio Específico';
        if ($regra->therapy_id)      $partes[] = $regra->therapy->name ?? 'Terapia Específica';
        if ($regra->service_type_id) $partes[] = $regra->serviceType->name ?? 'Ambiente Específico';

        return empty($partes) ? 'Regra Geral' : implode(' + ', $partes);
    }

    /**
     * Filtro de competência como intervalo — `whereYear`/`whereMonth` envolvem a coluna numa
     * função e o índice de `appointment_date` deixa de ser usado (57 mil linhas contra 5 mil).
     */
    private function noMes(int $ano, int $mes, ?int $ateDia = null, string $coluna = 'appointment_date'): callable
    {
        $inicio = Carbon::create($ano, $mes, 1)->startOfDay();

        $fim = $ateDia
            ? (clone $inicio)->addDays(min($ateDia, $inicio->daysInMonth))
            : (clone $inicio)->addMonth();

        return fn ($q) => $q->where($coluna, '>=', $inicio)->where($coluna, '<', $fim);
    }
}
