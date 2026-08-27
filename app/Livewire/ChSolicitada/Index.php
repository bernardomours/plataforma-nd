<?php

namespace App\Livewire\ChSolicitada;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\RequestedService;
use App\Models\Unit;
use App\Models\Agreement;
use App\Models\Therapy;
use Illuminate\Support\Facades\DB;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public $unit_id = '';
    public $month = '';
    public $year = '';
    public $search = '';

    public $agreement_id = '';
    public $agreements = [];

    public $therapy_id = '';
    public $therapies = [];

    public $faixa = '';

    public $units = [];
    public $availableYears = [];


    public const FAIXAS = [
        'cumprida' => [
            'rotulo' => 'Cumprida', 'descricao' => 'realizou 100% ou mais',
            'ponto' => 'bg-green-500',  'badge' => 'bg-green-50 text-green-700 border-green-200',
        ],
        'atencao' => [
            'rotulo' => 'Regular', 'descricao' => 'entre 80% e 99%',
            'ponto' => 'bg-blue-500', 'badge' => 'bg-blue-50 text-blue-700 border-blue-200',
        ],
        'critico' => [
            'rotulo' => 'Abaixo', 'descricao' => 'entre 50% e 79%',
            'ponto' => 'bg-yellow-500', 'badge' => 'bg-yellow-50 text-yellow-700 border-yellow-200',
        ],
        'grave' => [
            'rotulo' => 'Crítico', 'descricao' => 'abaixo de 50%',
            'ponto' => 'bg-red-500', 'badge' => 'bg-red-50 text-red-700 border-red-200',
        ],
    ];

    public function mount()
    {
        $this->units = Unit::orderBy('name')->get();

        $this->agreements = Agreement::orderBy('name')->get();

        $this->therapies = Therapy::orderBy('name')->get();

        for ($i = 0; $i <= 5; $i++) {
            $year = now()->subYears($i)->year;
            $this->availableYears[$year] = $year;
        }

        $this->month = now()->month;
        $this->year = now()->year;
    }

    private const SEMANAS_NO_MES = 4;

    private const SQL_PLANEJADA = '(COALESCE(
        requested_services.planned_sessions,
        COALESCE(NULLIF(requested_services.planned_hours, \'\') + 0, 0) * ' . self::SEMANAS_NO_MES . '
    ))';

    private const SQL_PLANEJADA_SEMANAL = 'COALESCE(NULLIF(requested_services.planned_hours, \'\') + 0, 0)';

    private const SQL_REALIZADA = 'COALESCE(ap.sessoes, 0)';

    // Um paciente pode ter várias requisições complementares para a mesma terapia no mês.
    // Solicitada/autorizada/planejada somam entre elas; o realizado é o mesmo para todas,
    // então usa MAX — somar contaria o mesmo atendimento uma vez por requisição.
    private const AGG_PLANEJADA = 'SUM(' . self::SQL_PLANEJADA . ')';
    private const AGG_REALIZADA = 'MAX(' . self::SQL_REALIZADA . ')';

    private function subqueryRealizado()
    {
        return DB::table('appointments')
            ->selectRaw('patient_id, therapy_id, service_type_id,
                         YEAR(appointment_date) as ano,
                         MONTH(appointment_date) as mes,
                         SUM(COALESCE(session_number, 0)) as sessoes,
                         SUM(TIME_TO_SEC(TIMEDIFF(check_out, check_in))) / 3600 as horas,
                         COUNT(*) as atendimentos')
            ->whereNotNull('check_in')
            ->whereNotNull('check_out')
            ->whereColumn('check_out', '>', 'check_in')
            ->when($this->year, fn ($q) => $q->whereYear('appointment_date', $this->year))
            ->when($this->month, fn ($q) => $q->whereMonth('appointment_date', $this->month))
            ->groupBy('patient_id', 'therapy_id', 'service_type_id', 'ano', 'mes');
    }

    private function baseQuery()
    {
        $query = RequestedService::query()
            ->selectRaw('MIN(requested_services.id) as id')
            ->selectRaw('requested_services.patient_id, requested_services.therapy_id, requested_services.service_type_id')
            ->selectRaw('MIN(requested_services.month_year) as month_year')
            ->selectRaw('COUNT(*) as requisicoes')
            ->selectRaw("GROUP_CONCAT(DISTINCT requested_services.requisition_number ORDER BY requested_services.requisition_number SEPARATOR ', ') as requisition_number")
            ->selectRaw('COALESCE(SUM(requested_services.requested_hours), 0) as requested_hours')
            ->selectRaw('COALESCE(SUM(requested_services.approved_hours), 0) as approved_hours')
            ->selectRaw(self::AGG_PLANEJADA . ' as planned_total')
            ->selectRaw('SUM(' . self::SQL_PLANEJADA_SEMANAL . ') as planned_weekly')
            ->selectRaw(self::AGG_REALIZADA . ' as realized_sessions')
            ->selectRaw('MAX(COALESCE(ap.horas, 0)) as realized_hours')
            ->selectRaw('MAX(COALESCE(ap.atendimentos, 0)) as realized_appointments')
            ->with([
                'patient' => fn ($q) => $q->withTrashed()->with('unit'),
                'therapy',
                'serviceType',
            ])
            // Paciente com saída registrada continua contando na competência em que foi
            // atendido: a alta em agosto não pode apagar o que ele fez em julho. O mês
            // seguinte se resolve sozinho — ninguém cadastra CH para quem já saiu.
            ->whereHas('patient', fn ($q) => $q->withTrashed())
            ->leftJoinSub($this->subqueryRealizado(), 'ap', function ($join) {
                $join->on('ap.patient_id', '=', 'requested_services.patient_id')
                     ->on('ap.therapy_id', '=', 'requested_services.therapy_id')
                     ->on('ap.service_type_id', '=', 'requested_services.service_type_id')
                     ->whereRaw('ap.ano = YEAR(requested_services.month_year)')
                     ->whereRaw('ap.mes = MONTH(requested_services.month_year)');
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->whereHas('patient', function ($subQ) {
                        $subQ->withTrashed()->where('name', 'like', '%' . $this->search . '%');
                    })->orWhere('requisition_number', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->unit_id, function ($query) {
                $query->whereHas('patient', function ($q) {
                    $q->withTrashed()->where('unit_id', $this->unit_id);
                });
            })
            ->when($this->agreement_id, function ($query) {
                $query->whereHas('patient', function ($q) {
                    $q->withTrashed()->where('agreement_id', $this->agreement_id);
                });
            })
            ->when($this->therapy_id, function ($query) {
                $query->where('requested_services.therapy_id', $this->therapy_id);
            })
            ->when($this->year, function ($query) {
                $query->whereYear('month_year', $this->year);
            })
            ->when($this->month, function ($query) {
                $query->whereMonth('month_year', $this->month);
            })
            ->groupBy(
                'requested_services.patient_id',
                'requested_services.therapy_id',
                'requested_services.service_type_id',
                DB::raw('YEAR(requested_services.month_year)'),
                DB::raw('MONTH(requested_services.month_year)')
            );

        $allowedUnits = auth()->user()->getAllowedUnitIds();

        if ($allowedUnits !== null) {
            $query->whereHas('patient', function ($q) use ($allowedUnits) {
                $q->withTrashed()->whereIn('unit_id', $allowedUnits);
            });
        }

        return $query;
    }

    private function aplicarFiltroFaixa($query)
    {
        if (! $this->faixa) {
            return $query;
        }

        // HAVING e não WHERE: depois do agrupamento a comparação é entre agregados.
        $planejada = self::AGG_PLANEJADA;
        $realizada = self::AGG_REALIZADA;

        if ($this->faixa === 'sem_plano') {
            return $query->havingRaw("{$planejada} <= 0");
        }

        $query->havingRaw("{$planejada} > 0");

        return match ($this->faixa) {
            'cumprida' => $query->havingRaw("{$realizada} >= {$planejada}"),
            'atencao'  => $query->havingRaw("{$realizada} < {$planejada} AND {$realizada} >= 0.8 * {$planejada}"),
            'critico'  => $query->havingRaw("{$realizada} < 0.8 * {$planejada} AND {$realizada} >= 0.5 * {$planejada}"),
            'grave'    => $query->havingRaw("{$realizada} < 0.5 * {$planejada}"),
            default    => $query,
        };
    }

    private function estatisticas(): object
    {
        $planejada = 'planned_total';
        $realizada = 'realized_sessions';

        $stats = DB::query()
            ->fromSub($this->baseQuery(), 'r')
            ->selectRaw("
                COUNT(*)                                                              as registros,
                COALESCE(SUM(requested_hours), 0)                                     as solicitadas,
                COALESCE(SUM(approved_hours), 0)                                      as aprovadas,
                COALESCE(SUM({$planejada}), 0)                                        as planejadas,
                COALESCE(SUM({$realizada}), 0)                                        as realizadas,
                COALESCE(SUM(realized_hours), 0)                                      as horas_realizadas,
                COALESCE(SUM(realized_appointments), 0)                               as atendimentos,
                SUM(CASE WHEN {$planejada} > 0 THEN 1 ELSE 0 END)                     as com_plano,
                COALESCE(SUM(CASE WHEN {$planejada} > 0 THEN {$realizada} ELSE 0 END), 0) as realizadas_com_plano,
                COALESCE(SUM(CASE WHEN {$planejada} > 0 AND {$realizada} < {$planejada}
                             THEN {$planejada} - {$realizada} ELSE 0 END), 0)         as deficit,
                COALESCE(SUM(CASE WHEN {$planejada} > 0 AND {$realizada} > {$planejada}
                             THEN {$realizada} - {$planejada} ELSE 0 END), 0)         as excedente,
                SUM(CASE WHEN {$planejada} > 0 AND {$realizada} >= {$planejada} THEN 1 ELSE 0 END) as faixa_cumprida,
                SUM(CASE WHEN {$planejada} > 0 AND {$realizada} <  {$planejada}
                          AND {$realizada} >= 0.8 * {$planejada} THEN 1 ELSE 0 END)   as faixa_atencao,
                SUM(CASE WHEN {$planejada} > 0 AND {$realizada} <  0.8 * {$planejada}
                          AND {$realizada} >= 0.5 * {$planejada} THEN 1 ELSE 0 END)   as faixa_critico,
                SUM(CASE WHEN {$planejada} > 0 AND {$realizada} <  0.5 * {$planejada} THEN 1 ELSE 0 END) as faixa_grave
            ")
            ->first();

        $stats->aderencia = $stats->planejadas > 0
            ? round(($stats->realizadas_com_plano / $stats->planejadas) * 100, 1)
            : null;

        $stats->cobertura = $stats->registros > 0
            ? round(($stats->com_plano / $stats->registros) * 100)
            : 0;

        $stats->sem_plano = $stats->registros - $stats->com_plano;

        return $stats;
    }

    public function filtrarPorFaixa($faixa)
    {
        $this->faixa = $this->faixa === $faixa ? '' : $faixa;
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset(['unit_id', 'month', 'year', 'search', 'agreement_id', 'therapy_id', 'faixa']);
        $this->resetPage();
    }

    public function updatedUnitId() { $this->resetPage(); }
    public function updatedMonth() { $this->resetPage(); }
    public function updatedYear() { $this->resetPage(); }
    public function updatedSearch() { $this->resetPage(); }

    public function updatedAgreementId() { $this->resetPage(); }

    public function updatedTherapyId() { $this->resetPage(); }

    public function updatedFaixa() { $this->resetPage(); }

    public function exportExcel()
    {
        // Mesma exigência da rota (/ch-solicitada é role:admin|manager): a ação do
        // Livewire não passa pelo middleware da rota, e aqui o dado exportado é mais
        // sensível que a tela (CPF e número da carteira do paciente).
        if (! auth()->user()->hasAnyRole(['admin', 'manager'])) {
            abort(403, 'Você não tem permissão para exportar esta planilha.');
        }

        // Mesmos filtros da tela (unidade, mês/ano, convênio, terapia, busca, faixa),
        // sem paginação: o Excel reflete exatamente o que está sendo visto na hora.
        $registros = $this->aplicarFiltroFaixa($this->baseQuery())->get();

        $fileName = 'ch-solicitada-' . now()->timezone('America/Fortaleza')->format('d-m-Y_H-i') . '.csv';

        return response()->streamDownload(function () use ($registros) {
            $file = fopen('php://output', 'w');

            // Força o Excel a reconhecer caracteres especiais (acentos, cedilhas, etc)
            fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            $separador = ';';

            fputcsv($file, [
                'PACIENTE', 'CPF', 'CARTEIRA', 'TERAPIA',
                'CH SOLICITADA', 'CH AUTORIZADA', 'CH PLANEJADA', 'CH REALIZADA',
            ], $separador, '"', '');

            foreach ($registros as $registro) {
                fputcsv($file, [
                    $registro->patient->name ?? '-',
                    $this->comoTextoNoExcel($this->formatarCpfParaExportacao($registro->patient->cpf ?? null)),
                    $this->comoTextoNoExcel(trim((string) ($registro->patient->agreement_number ?? '')) ?: '-'),
                    $registro->therapy->name ?? '-',
                    (int) round($registro->requested_hours ?? 0),
                    (int) round($registro->approved_hours ?? 0),
                    (int) round($this->planejadasNoMes($registro)),
                    (int) round($registro->realized_sessions ?? 0),
                ], $separador, '"', '');
            }

            fclose($file);
        }, $fileName);
    }

    /**
     * CPF na base está inconsistente: 253 pacientes com máscara, 258 só dígitos, 8 com
     * grafias soltas (espaço, ponto no lugar do traço). Aqui todo CPF de 11 dígitos sai
     * no mesmo formato — sem tentar corrigir os que têm menos de 11, para não mascarar
     * um erro de cadastro exibindo um número que pode estar errado.
     */
    private function formatarCpfParaExportacao(?string $cpf): string
    {
        $digitos = preg_replace('/\D/', '', (string) $cpf);

        if (strlen($digitos) === 11) {
            return substr($digitos, 0, 3) . '.' . substr($digitos, 3, 3) . '.' . substr($digitos, 6, 3) . '-' . substr($digitos, 9, 2);
        }

        return $digitos !== '' ? $digitos : '-';
    }

    /**
     * CPF e carteira sem máscara são só dígitos — o Excel os lê como número e derruba
     * o zero à esquerda (23 CPFs e 62 carteiras começam com zero na base). A sintaxe
     * ="valor" força Excel, Sheets e LibreOffice a tratar a célula como texto.
     */
    private function comoTextoNoExcel(string $valor): string
    {
        return '="' . str_replace('"', '""', $valor) . '"';
    }

    public function formatTime($decimalHours)
    {
        $decimalHours = (float) $decimalHours;
        $negativo = $decimalHours < 0;
        $decimalHours = abs($decimalHours);

        $hours = floor($decimalHours);

        $minutes = round(($decimalHours - $hours) * 60);

        if ($minutes == 60) {
            $hours++;
            $minutes = 0;
        }

        return ($negativo ? '-' : '') . sprintf('%02d:%02d', $hours, $minutes);
    }

    public function aderenciaDaLinha($registro): ?float
    {
        $planejada = (float) ($registro->planned_total ?? 0);

        if ($planejada <= 0) {
            return null;
        }

        return round(((float) $registro->realized_sessions / $planejada) * 100, 1);
    }

    public function planejadasNoMes($registro): float
    {
        return (float) ($registro->planned_total ?? 0);
    }

    public function faltaDaLinha($registro): ?float
    {
        $planejada = $this->planejadasNoMes($registro);

        if ($planejada <= 0) {
            return null;
        }

        return max(0, $planejada - (float) $registro->realized_sessions);
    }

    public function faixaDaLinha($registro): ?string
    {
        $aderencia = $this->aderenciaDaLinha($registro);

        if ($aderencia === null) {
            return null;
        }

        return match (true) {
            $aderencia >= 100 => 'cumprida',
            $aderencia >= 80  => 'atencao',
            $aderencia >= 50  => 'critico',
            default           => 'grave',
        };
    }

    public function render()
    {
        $stats = $this->estatisticas();

        $registros = $this->aplicarFiltroFaixa($this->baseQuery())
            ->orderByRaw('MIN(requested_services.month_year) DESC')
            ->paginate(15);

        return view('livewire.ch-solicitada.index', [
            'registros'      => $registros,
            'stats'          => $stats,
            'faixas'         => self::FAIXAS,
            'semanasNoMes'   => self::SEMANAS_NO_MES,
        ]);
    }
}
