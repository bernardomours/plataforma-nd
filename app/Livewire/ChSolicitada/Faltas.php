<?php

namespace App\Livewire\ChSolicitada;

use App\Models\Falta;
use App\Models\RequestedService;
use App\Models\Therapy;
use App\Models\Unit;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Acompanhamento de Faltas — lista TODA falta registrada (App\Models\Falta), com ou sem
 * CH solicitada cadastrada pro paciente/terapia/mês. Nasceu porque o único lugar que
 * mostrava falta até aqui era o ícone de detalhe em ChSolicitada\Index, e esse ícone só
 * existe nas linhas que já vêm de requested_services — paciente sem CH cadastrada tinha
 * falta gravada no banco sem nenhuma tela pra ver. Chegado por um botão em Controle de CH
 * (ChSolicitada\Index), não por item de menu — mesmo grupo de rota (admin|manager).
 */
#[Layout('layouts.app')]
class Faltas extends Component
{
    use WithPagination;

    public $unit_id = '';
    public $month = '';
    public $year = '';
    public string $search = '';
    public string $motivo = '';
    public bool $somenteSemCh = false;

    public $units = [];
    public $availableYears = [];

    public function mount()
    {
        $this->autorizarAcesso();

        $this->units = Unit::orderBy('name')->get();

        for ($i = 0; $i <= 5; $i++) {
            $year = now()->subYears($i)->year;
            $this->availableYears[$year] = $year;
        }

        $this->month = now()->month;
        $this->year = now()->year;
    }

    private function autorizarAcesso(): void
    {
        if (! auth()->user()->hasAnyRole(['admin', 'manager'])) {
            abort(403, 'Você não tem permissão para acessar o Acompanhamento de Faltas.');
        }
    }

    public function updatingUnitId() { $this->resetPage(); }
    public function updatingMonth() { $this->resetPage(); }
    public function updatingYear() { $this->resetPage(); }
    public function updatingSearch() { $this->resetPage(); }
    public function updatingMotivo() { $this->resetPage(); }
    public function updatingSomenteSemCh() { $this->resetPage(); }

    public function limparFiltros()
    {
        $this->reset(['unit_id', 'search', 'motivo', 'somenteSemCh']);
        $this->month = now()->month;
        $this->year = now()->year;
        $this->resetPage();
    }

    /**
     * Intervalo [início, fim) da competência filtrada. Mês vazio = ano inteiro — mesma
     * convenção de Producao\Glosas\Index. Sempre por intervalo, nunca whereYear()/
     * whereMonth() direto na coluna (ver "Armadilhas conhecidas" no CLAUDE.md).
     */
    private function periodo(): array
    {
        $ano = $this->year ?: now()->year;

        if ($this->month) {
            $inicio = Carbon::createFromDate($ano, (int) $this->month, 1)->startOfDay();
            $fim = $inicio->copy()->addMonthNoOverflow();
        } else {
            $inicio = Carbon::createFromDate($ano, 1, 1)->startOfDay();
            $fim = $inicio->copy()->addYear();
        }

        return [$inicio, $fim];
    }

    // Unidade e competência recortam a página inteira (KPIs + lista); busca, motivo e o
    // toggle "sem CH" recortam só a lista — mesmo padrão de ChSolicitada\Index/Glosas.
    private function escopoPagina()
    {
        [$inicio, $fim] = $this->periodo();

        $allowed = auth()->user()->getAllowedUnitIds();

        return Falta::query()
            ->whereBetween('date', [$inicio, $fim->copy()->subDay()])
            ->whereHas('patient', function ($q) use ($allowed) {
                $q->when($allowed !== null, fn ($qq) => $qq->whereIn('unit_id', $allowed));
            })
            ->when($this->unit_id, fn ($q) => $q->whereHas('patient', fn ($qq) => $qq->where('unit_id', $this->unit_id)));
    }

    private function escopoLista()
    {
        return $this->escopoPagina()
            ->when($this->search !== '', function ($q) {
                $termo = '%' . str_replace(['%', '_'], ['\%', '\_'], trim($this->search)) . '%';
                $q->whereHas('patient', fn ($qq) => $qq->where('name', 'like', $termo));
            })
            ->when($this->motivo !== '', fn ($q) => $q->where('motivo', $this->motivo))
            ->when($this->somenteSemCh, function ($q) {
                $q->whereNotIn('id', $this->idsComCh($q->clone()->pluck('id')));
            });
    }

    /**
     * Dos ids de Falta passados, devolve os que TÊM CH solicitada cadastrada pro mesmo
     * paciente+terapia+mês (mesma chave de agrupamento de ChSolicitada\Index). Uma query
     * batelada em vez de uma por linha — a tela pode listar bem mais de 15 faltas no ano
     * inteiro, diferente do ícone de detalhe em ChSolicitada\Index (que olha um paciente
     * só por vez).
     */
    private function idsComCh($idsFaltas): array
    {
        $faltas = Falta::whereIn('id', $idsFaltas)->get(['id', 'patient_id', 'therapy_id', 'date']);

        if ($faltas->isEmpty()) {
            return [];
        }

        $chaves = RequestedService::query()
            ->whereIn('patient_id', $faltas->pluck('patient_id')->unique())
            ->whereIn('therapy_id', $faltas->pluck('therapy_id')->unique())
            ->get(['patient_id', 'therapy_id', 'month_year'])
            ->map(fn ($r) => $r->patient_id . '|' . $r->therapy_id . '|' . Carbon::parse($r->month_year)->format('Y-m'))
            ->flip();

        return $faltas
            ->filter(fn ($f) => $chaves->has($f->patient_id . '|' . $f->therapy_id . '|' . $f->date->format('Y-m')))
            ->pluck('id')
            ->all();
    }

    private function kpis(): array
    {
        $todasNoEscopo = $this->escopoPagina()->get(['id', 'patient_id', 'therapy_id', 'date', 'motivo']);
        $total = $todasNoEscopo->count();

        $comCh = count($this->idsComCh($todasNoEscopo->pluck('id')));
        $semCh = $total - $comCh;

        $motivoMaisFrequente = $todasNoEscopo->countBy('motivo')->sortDesc()->keys()->first();

        return [
            'total' => $total,
            'sem_ch' => $semCh,
            'percentual_sem_ch' => $total > 0 ? round($semCh / $total * 100, 1) : 0,
            'motivo_mais_frequente' => $motivoMaisFrequente ? (Falta::MOTIVO_OPTIONS[$motivoMaisFrequente] ?? $motivoMaisFrequente) : null,
        ];
    }

    public function render()
    {
        $faltas = $this->escopoLista()
            ->with(['patient.unit', 'therapy', 'serviceType', 'registeredBy'])
            ->orderByDesc('date')
            ->paginate(20);

        // Marca, na própria página renderizada, quais linhas têm CH — evita repetir a
        // mesma lógica de idsComCh() dentro do blade.
        $comCh = collect($this->idsComCh($faltas->pluck('id')));
        foreach ($faltas as $falta) {
            $falta->temCh = $comCh->contains($falta->id);
        }

        return view('livewire.ch-solicitada.faltas', [
            'faltas' => $faltas,
            'kpis' => $this->kpis(),
            'unidadesLista' => $this->units,
            'motivoOptions' => Falta::MOTIVO_OPTIONS,
        ]);
    }
}
