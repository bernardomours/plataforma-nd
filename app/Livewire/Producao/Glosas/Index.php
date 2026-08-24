<?php

namespace App\Livewire\Producao\Glosas;

use App\Models\GlosaBatch;
use App\Models\GlosaItem;
use App\Models\GlosaReasonCode;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.producao')]
class Index extends Component
{
    use WithPagination;

    /** Recorte da página inteira: KPIs, gráfico e rankings respondem a estes dois. */
    public string $competencia = '';
    public $unidade_id = '';

    /** Recorte só da lista, para não produzir KPI sem sentido (ex.: "apresentado do CM89"). */
    public string $codigo = '';
    public string $busca = '';
    public string $situacao = 'glosados';

    public ?int $detalheId = null;

    public function updatingCompetencia()
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

    /** Recorte da página: competência e unidade. */
    private function escopo()
    {
        return GlosaItem::query()
            ->when($this->competencia, fn ($q) => $q->where('competencia', $this->competencia))
            ->when($this->unidade_id, fn ($q) => $q->where('unit_id', $this->unidade_id));
    }

    /** Recorte da lista: o escopo da página mais motivo, busca e situação. */
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

    /** Série histórica: a competência é o eixo, então só a unidade filtra aqui. */
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
                'atual'       => $this->competencia === $b->competencia->toDateString(),
            ]);
    }

    private function rankingMotivos()
    {
        return DB::table('glosa_reasons')
            ->join('glosa_items', 'glosa_items.id', '=', 'glosa_reasons.glosa_item_id')
            ->leftJoin('glosa_reason_codes', 'glosa_reason_codes.codigo', '=', 'glosa_reasons.codigo')
            ->where('glosa_items.status', '<>', GlosaItem::STATUS_LIBERADO)
            ->when($this->competencia, fn ($q) => $q->where('glosa_items.competencia', $this->competencia))
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

    /**
     * Base é o nome do relatório, não o profissional conciliado: só 59 dos 198 glosados de
     * 06/2026 acham atendimento pela guia, e restringir a eles escondia justamente o maior
     * caso do mês. Quando a conciliação existe, o nome do cadastro prevalece — vem com acento
     * e grafia corretos, enquanto o relatório varia ("JOSE" x "JOSÉ", sobrenome trocado de
     * ordem). O agrupamento é por nome normalizado, senão a mesma pessoa apareceria duas vezes.
     */
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

            // A chave é sempre o nome do relatório: ele existe em toda linha. Usar o nome do
            // cadastro quando há vínculo partiria a mesma pessoa em dois grupos, porque as
            // grafias divergem ("DÉBORA ... CÂMARA" no relatório, "DEBORA ... CAMARA" no cadastro).
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

    /**
     * "JOSÉ EDIVAN" e "JOSE EDIVAN" precisam cair no mesmo grupo. Str::ascii tem tabela
     * própria e dá sempre o mesmo resultado; `iconv` com //TRANSLIT depende do locale do
     * servidor e chega a devolver "D'EBORA", que não agruparia nada.
     */
    private function normalizar(?string $nome): string
    {
        return preg_replace('/\s+/', ' ', trim(Str::upper(Str::ascii((string) $nome))));
    }

    public function render()
    {
        $competencias = GlosaBatch::select('competencia')
            ->distinct()
            ->orderByDesc('competencia')
            ->pluck('competencia');

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
            'competenciasLista' => $competencias,
            'unidadesLista'     => Unit::whereIn('id', GlosaBatch::distinct()->pluck('unit_id')->filter())
                                    ->orderBy('name')->get(),
            'codigosLista'      => GlosaReasonCode::orderBy('codigo')->get(),
            'detalhe'           => $this->detalheId
                                    ? GlosaItem::with(['reasons.code', 'professional', 'patient', 'appointment', 'batch.unit'])
                                        ->find($this->detalheId)
                                    : null,
            // Só os GLOSADOS sem atendimento interessam à nota do ranking; contar todos os
            // itens sem vínculo daria um número muito maior e sem relação com o que está ali.
            'semVinculo'        => $this->escopo()->glosados()->naoConciliados()->count(),
        ]);
    }
}
