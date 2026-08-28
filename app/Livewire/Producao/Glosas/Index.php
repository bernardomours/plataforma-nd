<?php

namespace App\Livewire\Producao\Glosas;

use App\Models\GlosaBatch;
use App\Models\GlosaItem;
use App\Models\GlosaReasonCode;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.producao')]
class Index extends Component
{
    use WithPagination;

    // Mês vazio = ano inteiro (ex.: em 2027, ver 2026 todo). Ano é sempre exigido —
    // sem isso a consulta voltaria a varrer a tabela inteira sem filtro nenhum.
    public $mes;
    public $ano;
    public $unidade_id = '';

    public string $codigo = '';
    public string $busca = '';
    public string $situacao = 'glosados';

    public ?int $detalheId = null;

    public function mount()
    {
        // Papel pelo Spatie: componente não tinha checagem própria, só o middleware da
        // rota (role:admin|manager), que não é reexecutado pelas ações do Livewire.
        if (! auth()->user()->hasAnyRole(['admin', 'manager'])) {
            abort(403, 'Você não tem permissão para acessar os Relatórios de Glosa.');
        }

        $mesAnterior = now()->subMonthNoOverflow();
        $this->mes = $mesAnterior->month;
        $this->ano = $mesAnterior->year;
    }

    /** Início (inclusive) e fim (exclusivo) da competência selecionada, para filtrar por
     *  intervalo — nunca whereYear()/whereMonth(), que anulam o índice da coluna. */
    private function periodoCompetencia(): array
    {
        $inicio = Carbon::create((int) $this->ano, $this->mes ? (int) $this->mes : 1, 1)->startOfDay();
        $fim = $this->mes ? $inicio->copy()->addMonthNoOverflow() : $inicio->copy()->addYear();

        return [$inicio, $fim];
    }

    public function filtrarPorCompetencia(int $ano, int $mes)
    {
        // Clicar de novo no mês já ativo amplia a visão para o ano inteiro; clicar num
        // mês diferente troca o filtro normalmente.
        if ((int) $this->ano === $ano && (int) $this->mes === $mes) {
            $this->mes = null;
        } else {
            $this->ano = $ano;
            $this->mes = $mes;
        }

        $this->resetPage();
    }

    public function updatingMes()
    {
        $this->resetPage();
    }

    public function updatingAno()
    {
        $this->resetPage();
    }

    public function updatingUnidadeId()
    {
        $this->resetPage();
    }

    public function updatingCodigo()
    {
        $this->resetPage();
    }

    public function updatingBusca()
    {
        $this->resetPage();
    }

    public function updatingSituacao()
    {
        $this->resetPage();
    }

    public function limparFiltrosDaLista()
    {
        $this->reset(['codigo', 'busca', 'situacao']);
        $this->situacao = 'glosados';
        $this->resetPage();
    }

    public function verDetalhe(int $id)
    {
        $this->detalheId = $id;
    }

    public function fecharDetalhe()
    {
        $this->detalheId = null;
    }

    private function escopo()
    {
        [$inicio, $fim] = $this->periodoCompetencia();

        return GlosaItem::query()
            ->where('competencia', '>=', $inicio)
            ->where('competencia', '<', $fim)
            ->when($this->unidade_id, fn ($q) => $q->where('unit_id', $this->unidade_id));
    }

    private function lista()
    {
        return $this->escopo()
            ->when($this->situacao === 'glosados', fn ($q) => $q->glosados())
            ->when($this->situacao === 'nao_conciliados', fn ($q) => $q->glosados()->naoConciliados())
            ->when($this->codigo, fn ($q) => $q->whereHas('reasons', fn ($r) => $r->where('codigo', $this->codigo)))
            ->when($this->busca !== '', function ($q) {
                $termo = '%' . str_replace(['%', '_'], ['\%', '\_'], trim($this->busca)) . '%';

                $q->where(function ($sub) use ($termo) {
                    $sub->where('beneficiario_nome', 'like', $termo)
                        ->orWhere('guia', 'like', $termo)
                        ->orWhere('medico_nome', 'like', $termo);
                });
            });
    }

    private function kpis(): array
    {
        $r = $this->escopo()
            ->selectRaw('COUNT(*) itens, COALESCE(SUM(vl_apresentado),0) apresentado,
                         COALESCE(SUM(vl_liberado),0) liberado, COALESCE(SUM(vl_glosa),0) glosa,
                         COALESCE(SUM(CASE WHEN status <> ? THEN 1 ELSE 0 END),0) glosados,
                         COALESCE(SUM(appointment_id IS NOT NULL),0) conciliados',
                [GlosaItem::STATUS_LIBERADO])
            ->first();

        return [
            'itens'       => (int) $r->itens,
            'apresentado' => (float) $r->apresentado,
            'liberado'    => (float) $r->liberado,
            'glosa'       => (float) $r->glosa,
            'glosados'    => (int) $r->glosados,
            'conciliados' => (int) $r->conciliados,
            'percentual'  => $r->apresentado > 0 ? $r->glosa / $r->apresentado * 100 : 0,
        ];
    }

    private function evolucao()
    {
        return GlosaBatch::query()
            ->when($this->unidade_id, fn ($q) => $q->where('unit_id', $this->unidade_id))
            ->selectRaw('competencia, SUM(vl_apresentado) apresentado, SUM(vl_glosa) glosa')
            ->groupBy('competencia')
            ->orderBy('competencia')
            ->get()
            ->map(fn ($b) => (object) [
                'competencia' => $b->competencia,
                'rotulo'      => $b->competencia->format('m/y'),
                'titulo'      => $b->competencia->translatedFormat('F/Y'),
                'apresentado' => (float) $b->apresentado,
                'glosa'       => (float) $b->glosa,
                'percentual'  => $b->apresentado > 0 ? $b->glosa / $b->apresentado * 100 : 0,
                'atual'       => (int) $b->competencia->year === (int) $this->ano
                                  && (! $this->mes || (int) $b->competencia->month === (int) $this->mes),
            ]);
    }

    private function rankingMotivos()
    {
        [$inicio, $fim] = $this->periodoCompetencia();

        return DB::table('glosa_reasons')
            ->join('glosa_items', 'glosa_items.id', '=', 'glosa_reasons.glosa_item_id')
            ->leftJoin('glosa_reason_codes', 'glosa_reason_codes.codigo', '=', 'glosa_reasons.codigo')
            ->where('glosa_items.status', '<>', GlosaItem::STATUS_LIBERADO)
            ->where('glosa_items.competencia', '>=', $inicio)
            ->where('glosa_items.competencia', '<', $fim)
            ->when($this->unidade_id, fn ($q) => $q->where('glosa_items.unit_id', $this->unidade_id))
            ->selectRaw('glosa_reasons.codigo,
                         COALESCE(glosa_reason_codes.descricao, MAX(glosa_reasons.descricao)) descricao,
                         COUNT(*) ocorrencias, SUM(glosa_items.vl_glosa) valor')
            ->groupBy('glosa_reasons.codigo', 'glosa_reason_codes.descricao')
            ->orderByDesc('ocorrencias')
            ->limit(10)
            ->get();
    }

    private function rankingBeneficiarios()
    {
        return $this->escopo()->glosados()
            ->selectRaw('beneficiario_nome nome, COUNT(*) ocorrencias, SUM(vl_glosa) valor')
            ->whereNotNull('beneficiario_nome')
            ->groupBy('beneficiario_nome')
            ->orderByDesc('ocorrencias')
            ->limit(10)
            ->get();
    }

    private function rankingProfissionais()
    {
        $itens = $this->escopo()->glosados()
            ->with(['professional' => fn ($q) => $q->select('id', 'name', 'deleted_at')])
            ->get(['id', 'guia', 'vl_glosa', 'medico_nome', 'professional_id']);

        $agrupado = [];

        foreach ($itens as $item) {
            $nomeRelatorio = trim((string) $item->medico_nome);

            if ($nomeRelatorio === '' && ! $item->professional) {
                continue;
            }

            $chave = $this->normalizar($nomeRelatorio ?: $item->professional->name);

            $agrupado[$chave] ??= [
                'nome' => $nomeRelatorio ?: '—', 'guias' => [], 'valor' => 0.0,
                'vinculado' => false, 'inativo' => false,
            ];

            if ($item->professional) {
                $agrupado[$chave]['nome']      = $item->professional->name;
                $agrupado[$chave]['vinculado'] = true;
                $agrupado[$chave]['inativo']   = (bool) $item->professional->deleted_at;
            }

            $agrupado[$chave]['guias'][$item->guia ?? 'item-' . $item->id] = true;
            $agrupado[$chave]['valor'] += (float) $item->vl_glosa;
        }

        return collect($agrupado)
            ->map(fn ($p) => (object) [
                'nome'        => $p['nome'],
                'ocorrencias' => count($p['guias']),
                'valor'       => $p['valor'],
                'vinculado'   => $p['vinculado'],
                'inativo'     => $p['inativo'],
            ])
            ->sortByDesc('ocorrencias')
            ->take(10)
            ->values();
    }

    private function normalizar(?string $nome): string
    {
        return preg_replace('/\s+/', ' ', trim(Str::upper(Str::ascii((string) $nome))));
    }

    public function render()
    {
        // Ano sempre inclui o corrente, mesmo sem glosa lançada ainda — é o caso que
        // motivou a separação de mês/ano: poder escolher o ano anterior por inteiro
        // assim que o calendário virar.
        $anosLista = GlosaBatch::selectRaw('DISTINCT YEAR(competencia) as ano')
            ->pluck('ano')
            ->push(now()->year)
            ->unique()
            ->sortDesc()
            ->values();

        $mesesLista = collect(range(1, 12))
            ->mapWithKeys(fn ($m) => [$m => ucfirst(Carbon::create(2000, $m, 1)->translatedFormat('F'))]);

        $kpis = $this->kpis();

        return view('livewire.producao.glosas.index', [
            'kpis'            => $kpis,
            'evolucao'        => $this->evolucao(),
            'motivos'         => $this->rankingMotivos(),
            'beneficiarios'   => $this->rankingBeneficiarios(),
            'profissionais'   => $this->rankingProfissionais(),
            'itens'           => $this->lista()
                                    ->with(['reasons.code', 'professional', 'appointment'])
                                    ->orderByDesc('vl_glosa')
                                    ->orderByDesc('dt_item')
                                    ->paginate(15),
            'anosLista'         => $anosLista,
            'mesesLista'        => $mesesLista,
            'unidadesLista'     => Unit::whereIn('id', GlosaBatch::distinct()->pluck('unit_id')->filter())
                                    ->orderBy('name')->get(),
            'codigosLista'      => GlosaReasonCode::orderBy('codigo')->get(),
            'detalhe'           => $this->detalheId
                                    ? GlosaItem::with(['reasons.code', 'professional', 'patient', 'appointment', 'batch.unit'])
                                        ->find($this->detalheId)
                                    : null,
            'semVinculo'        => $this->escopo()->glosados()->naoConciliados()->count(),
        ]);
    }
}
