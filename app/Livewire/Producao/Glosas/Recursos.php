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
 * Acompanhamento manual do recurso de glosa: lote, valor recursado, valor acatado e status,
 * um registro por lote de glosa (glosa_batches). Preenchido por admin, manager ou
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
    public string $lote = '';
    public string $valor_recursado = '';
    public string $valor_acatado = '';
    public string $modal_status = '';

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
            ->when($this->status === 'sem_registro', fn ($q) => $q->doesntHave('recurso'))
            ->when(
                array_key_exists($this->status, GlosaRecurso::STATUS_OPTIONS),
                fn ($q) => $q->whereHas('recurso', fn ($r) => $r->where('status', $this->status))
            );
    }

    private function kpis(): array
    {
        $r = $this->escopoPagina()
            ->leftJoin('glosa_recursos', 'glosa_recursos.glosa_batch_id', '=', 'glosa_batches.id')
            ->selectRaw('
                COUNT(*) as total_lotes,
                COALESCE(SUM(glosa_batches.vl_glosa), 0) as total_glosado,
                COALESCE(SUM(glosa_recursos.valor_recursado), 0) as total_recursado,
                COALESCE(SUM(glosa_recursos.valor_acatado), 0) as total_acatado,
                COALESCE(SUM(CASE WHEN glosa_recursos.id IS NULL THEN 1 ELSE 0 END), 0) as sem_recurso
            ')
            ->first();

        return [
            'total_lotes'         => (int) $r->total_lotes,
            'sem_recurso'         => (int) $r->sem_recurso,
            'total_glosado'       => (float) $r->total_glosado,
            'total_recursado'     => (float) $r->total_recursado,
            'total_acatado'       => (float) $r->total_acatado,
            'conversao_recursado' => $r->total_glosado > 0 ? $r->total_recursado / $r->total_glosado * 100 : 0,
            'conversao_acatado'   => $r->total_recursado > 0 ? $r->total_acatado / $r->total_recursado * 100 : 0,
        ];
    }

    public function abrirModal(int $batchId)
    {
        $this->autorizarAcesso();

        $batch = $this->escopoUnidade()->with('recurso')->findOrFail($batchId);

        $this->editingBatchId = $batch->id;
        $this->lote = $batch->recurso->lote ?? '';
        $this->valor_recursado = $batch->recurso && $batch->recurso->valor_recursado !== null
            ? (string) $batch->recurso->valor_recursado : '';
        $this->valor_acatado = $batch->recurso && $batch->recurso->valor_acatado !== null
            ? (string) $batch->recurso->valor_acatado : '';
        $this->modal_status = $batch->recurso->status ?? '';

        $this->resetValidation();
        $this->isModalOpen = true;
    }

    public function fecharModal()
    {
        $this->isModalOpen = false;
        $this->reset(['editingBatchId', 'lote', 'valor_recursado', 'valor_acatado', 'modal_status']);
        $this->resetValidation();
    }

    public function salvar()
    {
        $this->autorizarAcesso();

        $this->validate([
            'lote'            => 'nullable|string|max:30',
            'valor_recursado' => 'nullable|numeric|min:0',
            'valor_acatado'   => 'nullable|numeric|min:0',
            'modal_status'    => ['nullable', Rule::in(array_keys(GlosaRecurso::STATUS_OPTIONS))],
        ]);

        if ($this->valor_acatado !== '' && $this->valor_recursado !== ''
            && (float) $this->valor_acatado > (float) $this->valor_recursado) {
            $this->addError('valor_acatado', 'O valor acatado não pode ser maior que o valor recursado.');

            return;
        }

        // Reconfirma que o lote está no escopo permitido do usuário — mesma proteção de
        // IDOR já usada em outras telas do Livewire (a checagem em abrirModal não é
        // redundante com esta: o payload de salvar() chega direto, sem reabrir o modal).
        $batch = $this->escopoUnidade()->findOrFail($this->editingBatchId);

        GlosaRecurso::updateOrCreate(
            ['glosa_batch_id' => $batch->id],
            [
                'lote'            => $this->lote !== '' ? $this->lote : null,
                'valor_recursado' => $this->valor_recursado !== '' ? $this->valor_recursado : null,
                'valor_acatado'   => $this->valor_acatado !== '' ? $this->valor_acatado : null,
                'status'          => $this->modal_status !== '' ? $this->modal_status : null,
                'registered_by'   => auth()->id(),
            ]
        );

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
                                    ->with('recurso')
                                    ->orderByDesc('competencia')
                                    ->orderBy('prestador_nome')
                                    ->paginate(15),
            'competenciasLista' => $competencias,
            'unidadesLista'     => $unidades,
            'statusOptions'     => GlosaRecurso::STATUS_OPTIONS,
        ]);
    }
}
