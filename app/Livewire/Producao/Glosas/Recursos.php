<?php

namespace App\Livewire\Producao\Glosas;

use App\Models\GlosaBatch;
use App\Models\GlosaRecurso;
use App\Models\Unit;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Acompanhamento manual do recurso de glosa: lote, valor recursado, valor acatado e status.
 * Um lote de glosa (glosa_batches) pode ter mais de um recurso — raro, mas acontece (reenvio,
 * nova tentativa) — por isso o formulário é um repeater, mesmo padrão das requisições
 * complementares de CH em Pacientes\CargaHoraria. Preenchido por admin, manager ou
 * administrative — a única tela de glosas que administrative acessa; Relatórios Mensais
 * continua admin|manager.
 */
#[Layout('layouts.producao')]
class Recursos extends Component
{
    use WithPagination;

    public string $competencia = '';
    public $unidade_id = '';
    public string $busca = '';
    public string $status = '';

    public bool $isModalOpen = false;
    public ?int $editingBatchId = null;
    public array $recursosForm = [];

    public function mount()
    {
        $this->autorizarAcesso();
    }

    private function autorizarAcesso(): void
    {
        if (! auth()->user()->hasAnyRole(['admin', 'manager', 'administrative'])) {
            abort(403, 'Você não tem permissão para acessar o Acompanhamento de Recursos.');
        }
    }

    public function updatingCompetencia() { $this->resetPage(); }
    public function updatingUnidadeId() { $this->resetPage(); }
    public function updatingBusca() { $this->resetPage(); }
    public function updatingStatus() { $this->resetPage(); }

    public function limparFiltros()
    {
        $this->reset(['competencia', 'unidade_id', 'busca', 'status']);
        $this->resetPage();
    }

    private function allowedUnitIds(): ?array
    {
        return auth()->user()->getAllowedUnitIds();
    }

    /** Base de autorização: só lotes com glosa e da unidade permitida. Sem filtros de tela. */
    private function escopoUnidade()
    {
        $allowed = $this->allowedUnitIds();

        return GlosaBatch::query()
            ->where('vl_glosa', '>', 0)
            ->when($allowed !== null, fn ($q) => $q->whereIn('unit_id', $allowed));
    }

    // Competência e unidade recortam a página inteira (KPIs + lista); busca e status
    // recortam só a lista — mesmo padrão de Producao\Glosas\Index.
    private function escopoPagina()
    {
        return $this->escopoUnidade()
            ->when($this->competencia, fn ($q) => $q->where('competencia', $this->competencia))
            ->when($this->unidade_id, fn ($q) => $q->where('unit_id', $this->unidade_id));
    }

    private function escopoLista()
    {
        return $this->escopoPagina()
            ->when($this->busca !== '', function ($q) {
                $termo = '%' . str_replace(['%', '_'], ['\%', '\_'], trim($this->busca)) . '%';

                $q->where(function ($sub) use ($termo) {
                    $sub->where('prestador_nome', 'like', $termo)
                        ->orWhere('prestador_codigo', 'like', $termo);
                });
            })
            ->when($this->status === 'sem_registro', fn ($q) => $q->doesntHave('recursos'))
            ->when(
                array_key_exists($this->status, GlosaRecurso::STATUS_OPTIONS),
                fn ($q) => $q->whereHas('recursos', fn ($r) => $r->where('status', $this->status))
            );
    }

    private function kpis(): array
    {
        $idsNoEscopo = $this->escopoPagina()->pluck('glosa_batches.id');

        $totalGlosado = $this->escopoPagina()->sum('vl_glosa');
        $semRecurso = $this->escopoPagina()->doesntHave('recursos')->count();

        // Soma direto em glosa_recursos, filtrando pelos ids do escopo — evita o fan-out
        // de somar glosa_batches.vl_glosa via join quando um lote tem mais de um recurso.
        $agregado = GlosaRecurso::whereIn('glosa_batch_id', $idsNoEscopo)
            ->selectRaw('COALESCE(SUM(valor_recursado),0) as total_recursado, COALESCE(SUM(valor_acatado),0) as total_acatado')
            ->first();

        $totalRecursado = (float) $agregado->total_recursado;
        $totalAcatado = (float) $agregado->total_acatado;

        return [
            'total_lotes'         => $idsNoEscopo->count(),
            'sem_recurso'         => $semRecurso,
            'total_glosado'       => (float) $totalGlosado,
            'total_recursado'     => $totalRecursado,
            'total_acatado'       => $totalAcatado,
            'conversao_recursado' => $totalGlosado > 0 ? $totalRecursado / $totalGlosado * 100 : 0,
            'conversao_acatado'   => $totalRecursado > 0 ? $totalAcatado / $totalRecursado * 100 : 0,
        ];
    }

    public function abrirModal(int $batchId)
    {
        $this->autorizarAcesso();

        $batch = $this->escopoUnidade()->with('recursos')->findOrFail($batchId);

        $this->editingBatchId = $batch->id;
        $this->recursosForm = $batch->recursos->map(fn ($r) => [
            'id'              => $r->id,
            'lote'            => $r->lote ?? '',
            'valor_recursado' => $r->valor_recursado !== null ? (string) $r->valor_recursado : '',
            'valor_acatado'   => $r->valor_acatado !== null ? (string) $r->valor_acatado : '',
            'status'          => $r->status ?? '',
        ])->all();

        if (empty($this->recursosForm)) {
            $this->adicionarLinhaRecurso();
        }

        $this->resetValidation();
        $this->isModalOpen = true;
    }

    public function adicionarLinhaRecurso()
    {
        $this->recursosForm[] = [
            'id' => null, 'lote' => '', 'valor_recursado' => '', 'valor_acatado' => '', 'status' => '',
        ];
    }

    public function removerLinhaRecurso(int $index)
    {
        $this->autorizarAcesso();

        $linha = $this->recursosForm[$index] ?? null;

        if ($linha === null) {
            return;
        }

        if (! empty($linha['id'])) {
            // Confirma que o lote continua no escopo permitido antes de excluir.
            $this->escopoUnidade()->findOrFail($this->editingBatchId);

            GlosaRecurso::where('id', $linha['id'])
                ->where('glosa_batch_id', $this->editingBatchId)
                ->delete();
        }

        unset($this->recursosForm[$index]);
        $this->recursosForm = array_values($this->recursosForm);

        if (empty($this->recursosForm)) {
            $this->adicionarLinhaRecurso();
        }
    }

    public function fecharModal()
    {
        $this->isModalOpen = false;
        $this->reset(['editingBatchId', 'recursosForm']);
        $this->resetValidation();
    }

    public function salvar()
    {
        $this->autorizarAcesso();

        $this->validate([
            'recursosForm.*.lote'            => 'nullable|string|max:30',
            'recursosForm.*.valor_recursado' => 'nullable|numeric|min:0',
            'recursosForm.*.valor_acatado'   => 'nullable|numeric|min:0',
            'recursosForm.*.status'          => ['nullable', Rule::in(array_keys(GlosaRecurso::STATUS_OPTIONS))],
        ]);

        foreach ($this->recursosForm as $i => $linha) {
            if ($linha['valor_acatado'] !== '' && $linha['valor_recursado'] !== ''
                && (float) $linha['valor_acatado'] > (float) $linha['valor_recursado']) {
                $this->addError("recursosForm.$i.valor_acatado", 'O valor acatado não pode ser maior que o valor recursado.');
            }
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        // Reconfirma que o lote está no escopo permitido do usuário — mesma proteção de
        // IDOR já usada em outras telas do Livewire.
        $batch = $this->escopoUnidade()->findOrFail($this->editingBatchId);

        foreach ($this->recursosForm as $linha) {
            $vazia = $linha['lote'] === '' && $linha['valor_recursado'] === ''
                && $linha['valor_acatado'] === '' && $linha['status'] === '';

            // Linha em branco que nunca existiu: não cria registro vazio.
            if ($vazia && empty($linha['id'])) {
                continue;
            }

            $dados = [
                'glosa_batch_id'  => $batch->id,
                'lote'            => $linha['lote'] !== '' ? $linha['lote'] : null,
                'valor_recursado' => $linha['valor_recursado'] !== '' ? $linha['valor_recursado'] : null,
                'valor_acatado'   => $linha['valor_acatado'] !== '' ? $linha['valor_acatado'] : null,
                'status'          => $linha['status'] !== '' ? $linha['status'] : null,
                'registered_by'   => auth()->id(),
            ];

            if (! empty($linha['id'])) {
                GlosaRecurso::where('id', $linha['id'])->where('glosa_batch_id', $batch->id)->update($dados);
            } else {
                GlosaRecurso::create($dados);
            }
        }

        $this->fecharModal();
    }

    public function render()
    {
        $allowed = $this->allowedUnitIds();

        $competencias = $this->escopoUnidade()
            ->select('competencia')->distinct()->orderByDesc('competencia')->pluck('competencia');

        $unidades = Unit::whereIn('id', $this->escopoUnidade()->distinct()->pluck('unit_id')->filter())
            ->orderBy('name')->get();

        return view('livewire.producao.glosas.recursos', [
            'kpis'              => $this->kpis(),
            'lotes'             => $this->escopoLista()
                                    ->withCount('recursos')
                                    ->withSum('recursos', 'valor_recursado')
                                    ->withSum('recursos', 'valor_acatado')
                                    ->with('recursos')
                                    ->orderByDesc('competencia')
                                    ->orderBy('prestador_nome')
                                    ->paginate(15),
            'competenciasLista' => $competencias,
            'unidadesLista'     => $unidades,
            'statusOptions'     => GlosaRecurso::STATUS_OPTIONS,
        ]);
    }
}
