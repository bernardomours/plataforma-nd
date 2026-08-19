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

    /**
     * Filtro por faixa de aderência (realizada / planejada).
     * Vazio = todas. Valores: cumprida | atencao | critico | grave | sem_plano
     */
    public $faixa = '';

    public $units = [];
    public $availableYears = [];

    /**
     * Faixas de aderência usadas no painel e no filtro.
     * Centralizadas aqui para que rótulo, cor e limite não se descolem entre
     * o cálculo (SQL) e a exibição (Blade).
     */
    /**
     * As classes de cor são gravadas por extenso de propósito: o Tailwind detecta classes
     * varrendo o código-fonte, então algo como "bg-{$cor}-500" seria removido no purge e
     * a cor não apareceria em produção.
     */
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

    /**
     * Semanas consideradas por mês para converter o planejamento semanal em mensal.
     *
     * Usado apenas como RESERVA, para os registros antigos que só têm o valor semanal.
     * A partir da derivação pela agenda, o mensal é apurado com precisão (quantas segundas,
     * terças etc. o mês tem, descontados feriados) e gravado em planned_sessions.
     */
    private const SEMANAS_NO_MES = 4;

    /**
     * Sessões planejadas no mês.
     *
     * Dois ajustes embutidos:
     *  - planned_hours é varchar(255) no banco (a migration make_planned_hours_nullable
     *    trocou decimal(8,2) por string), então convertemos explicitamente para número
     *    em vez de depender de coerção implícita;
     *  - multiplicamos pelas semanas do mês, pois o campo é semanal.
     *
     * Apesar do nome da coluna, o valor NÃO é hora: é quantidade de sessões.
     */
    private const SQL_PLANEJADA = '(COALESCE(
        requested_services.planned_sessions,
        COALESCE(NULLIF(requested_services.planned_hours, \'\') + 0, 0) * ' . self::SEMANAS_NO_MES . '
    ))';

    /** Valor SEMANAL, exibido como contexto embaixo do total mensal. */
    private const SQL_PLANEJADA_SEMANAL = 'COALESCE(NULLIF(requested_services.planned_hours, \'\') + 0, 0)';

    /**
     * Sessões efetivamente realizadas, vindas do JOIN agregado.
     *
     * Usamos appointments.session_number, que é o campo onde o sistema já grava a
     * conversão de duração em sessões (40 min = 1 sessão; ABA de paciente Unimed = 60 min).
     * Conferido contra os dados: a regra bate em 93% dos atendimentos ABA+Unimed e em 97%
     * dos demais. Somar session_number é também o que a tela de Terapias Realizadas faz,
     * e nas importações da Unimed o valor vem da própria planilha do convênio — ou seja,
     * é o número que vale para faturamento. Recalcular pela duração aqui divergiria disso.
     */
    private const SQL_REALIZADA = 'COALESCE(ap.sessoes, 0)';

    /**
     * Subconsulta que soma as SESSÕES realizadas por competência.
     *
     * Por que agregada e não correlacionada (como era antes):
     *  - a versão anterior rodava um SELECT por linha listada;
     *  - e, principalmente, casava apenas paciente + terapia + mês, IGNORANDO o tipo de
     *    atendimento. Como requested_services tem uma linha por tipo, o mesmo total era
     *    atribuído a todas as linhas do mesmo paciente/terapia/mês e somado novamente em
     *    cada uma — 90 grupos duplicados na base.
     *
     * Também descartamos aqui os registros que corrompem o cálculo:
     *  - check_out nulo (atendimento sem fechamento);
     *  - check_out anterior ao check_in (erro de digitação): 4 registros que geravam
     *    duração negativa.
     *
     * A duração em horas continua sendo somada porque é exibida como informação
     * secundária na tabela — mas quem manda no indicador é a contagem de sessões.
     */
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
            // Empurra o recorte de período para dentro da agregação: sem isto o MySQL
            // agruparia as ~33 mil linhas inteiras a cada carregamento da tela.
            ->when($this->year, fn ($q) => $q->whereYear('appointment_date', $this->year))
            ->when($this->month, fn ($q) => $q->whereMonth('appointment_date', $this->month))
            ->groupBy('patient_id', 'therapy_id', 'service_type_id', 'ano', 'mes');
    }

    /**
     * Consulta base — ponto ÚNICO de construção.
     *
     * SEGURANÇA: o isolamento por unidade é aplicado aqui dentro, e não no render().
     * Antes, os totais e a listagem eram montados em lugares diferentes; centralizar
     * garante que nenhum agregado futuro escape do escopo do usuário por esquecimento.
     */
    private function baseQuery()
    {
        $query = RequestedService::query()
            ->select('requested_services.*')
            ->selectRaw(self::SQL_REALIZADA . ' as realized_sessions')
            // Alias distinto da coluna física planned_sessions: com 'requested_services.*'
            // no select, repetir o nome quebra a subconsulta com "Duplicate column name".
            ->selectRaw(self::SQL_PLANEJADA . ' as planned_total')
            ->selectRaw(self::SQL_PLANEJADA_SEMANAL . ' as planned_weekly')
            // Informação secundária, só para contexto na tabela.
            ->selectRaw('COALESCE(ap.horas, 0) as realized_hours')
            ->selectRaw('COALESCE(ap.atendimentos, 0) as realized_appointments')
            ->with(['patient.unit', 'therapy', 'serviceType'])
            ->has('patient')
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
                        $subQ->where('name', 'like', '%' . $this->search . '%');
                    })->orWhere('requisition_number', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->unit_id, function ($query) {
                $query->whereHas('patient', function ($q) {
                    $q->where('unit_id', $this->unit_id);
                });
            })
            ->when($this->agreement_id, function ($query) {
                $query->whereHas('patient', function ($q) {
                    $q->where('agreement_id', $this->agreement_id);
                });
            })
            ->when($this->therapy_id, function ($query) {
                // Coluna qualificada de propósito: a subconsulta 'ap' do leftJoinSub também
                // expõe therapy_id, e sem o prefixo o MySQL acusaria coluna ambígua.
                $query->where('requested_services.therapy_id', $this->therapy_id);
            })
            ->when($this->year, function ($query) {
                $query->whereYear('month_year', $this->year);
            })
            ->when($this->month, function ($query) {
                $query->whereMonth('month_year', $this->month);
            });

        // SEGURANÇA (multi-tenant): restringe às unidades permitidas ao usuário.
        // null = admin/manager = acesso global, mesmo contrato do resto do sistema.
        $allowedUnits = auth()->user()->getAllowedUnitIds();

        if ($allowedUnits !== null) {
            $query->whereHas('patient', function ($q) use ($allowedUnits) {
                $q->whereIn('unit_id', $allowedUnits);
            });
        }

        return $query;
    }

    /**
     * Aplica o filtro de faixa. Fica separado da baseQuery porque o painel de faixas
     * precisa contar o universo INTEIRO — se a faixa entrasse na base, os cartões
     * passariam a contar apenas a própria faixa selecionada.
     */
    private function aplicarFiltroFaixa($query)
    {
        if (! $this->faixa) {
            return $query;
        }

        $planejada = self::SQL_PLANEJADA;
        $realizada = self::SQL_REALIZADA;

        if ($this->faixa === 'sem_plano') {
            return $query->whereRaw("{$planejada} <= 0");
        }

        $query->whereRaw("{$planejada} > 0");

        return match ($this->faixa) {
            'cumprida' => $query->whereRaw("{$realizada} >= {$planejada}"),
            'atencao'  => $query->whereRaw("{$realizada} < {$planejada} AND {$realizada} >= 0.8 * {$planejada}"),
            'critico'  => $query->whereRaw("{$realizada} < 0.8 * {$planejada} AND {$realizada} >= 0.5 * {$planejada}"),
            'grave'    => $query->whereRaw("{$realizada} < 0.5 * {$planejada}"),
            default    => $query,
        };
    }

    /**
     * Todos os totais em UMA consulta.
     *
     * Antes eram quatro idas ao banco — três SUM e um ->get()->sum('realized_hours'),
     * este último trazendo TODAS as linhas do filtro para a memória do PHP só para somar
     * uma coluna. Agora envolvemos a consulta base numa derivada e agregamos no MySQL.
     */
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

        // Percentual de aderência: comparado apenas contra o que TEM planejamento
        // informado, senão registros sem plano derrubariam o indicador artificialmente.
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

    // CORREÇÃO: o hook antigo chamava-se updatedAgreement(), mas a propriedade é
    // $agreement_id — o Livewire monta o nome a partir da propriedade, então o método
    // nunca era chamado e trocar o convênio não voltava para a primeira página.
    public function updatedAgreementId() { $this->resetPage(); }

    public function updatedTherapyId() { $this->resetPage(); }

    public function updatedFaixa() { $this->resetPage(); }

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

    /**
     * Aderência de uma linha, em percentual. null quando não há planejamento informado.
     * Compara SESSÕES realizadas contra SESSÕES planejadas no mês (semanal x 4).
     */
    public function aderenciaDaLinha($registro): ?float
    {
        $planejada = (float) ($registro->planned_total ?? 0);

        if ($planejada <= 0) {
            return null;
        }

        return round(((float) $registro->realized_sessions / $planejada) * 100, 1);
    }

    /** Sessões planejadas no mês para a linha (o campo do banco é semanal). */
    public function planejadasNoMes($registro): float
    {
        return (float) ($registro->planned_total ?? 0);
    }

    /** Sessões que faltaram. null quando não há planejamento informado. */
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
            ->orderBy('month_year', 'desc')
            ->paginate(15);

        return view('livewire.ch-solicitada.index', [
            'registros'      => $registros,
            'stats'          => $stats,
            'faixas'         => self::FAIXAS,
            'semanasNoMes'   => self::SEMANAS_NO_MES,
        ]);
    }
}
