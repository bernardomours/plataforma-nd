<?php

namespace App\Livewire\Controles;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\Models\Activity;
use App\Models\MovementHistory;
use App\Models\Patient;
use App\Models\Professional;
use App\Models\Unit;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $tab = 'todos';

    public $unit_id = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingUnitId()
    {
        $this->resetPage();
    }

    public function setTab($tab)
    {
        $this->tab = $tab;
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'unit_id']);
        $this->resetPage();
    }

    private function pacientesDaUnidade(int $unitId)
    {
        return Patient::withoutGlobalScopes()->where('unit_id', $unitId)->select('id');
    }

    private function profissionaisDaUnidade(int $unitId)
    {
        return Professional::withTrashed()
            ->whereHas('units', fn ($u) => $u->where('units.id', $unitId))
            ->select('id');
    }

    public function unidadeDoRegistro(Activity $atividade): string
    {
        $subject = $atividade->subject;

        if ($subject instanceof MovementHistory) {
            $subject = $this->resolverMoveable($subject);
        }

        if ($subject instanceof Patient) {
            return $subject->unit?->city ?? '—';
        }

        if ($subject instanceof Professional) {
            $nomes = $subject->units->pluck('city');

            return $nomes->isEmpty() ? '—' : $nomes->implode(', ');
        }

        // Fallback: registro já excluído do banco — usa o snapshot da própria atividade.
        $unitId = data_get($atividade->properties, 'attributes.unit_id')
            ?? data_get($atividade->attribute_changes, 'attributes.unit_id');

        if ($unitId) {
            return Unit::find($unitId)?->city ?? '—';
        }

        return '—';
    }

    private function resolverMoveable(MovementHistory $movimento)
    {
        if ($movimento->moveable) {
            return $movimento->moveable;
        }

        $tipo = $movimento->moveable_type;

        if (! $tipo || ! class_exists($tipo)) {
            return null;
        }

        if (! in_array(SoftDeletes::class, class_uses_recursive($tipo))) {
            return null;
        }

        return $tipo::withTrashed()->find($movimento->moveable_id);
    }

    public function render()
    {
        $query = Activity::with([
            'causer',
            'subject' => function (MorphTo $morphTo) {
                $morphTo->morphWith([
                    MovementHistory::class => ['moveable'],
                    Patient::class         => ['unit'],
                    Professional::class    => ['units'],
                ]);
            },
        ])->latest();

        if ($this->tab === 'atualizacoes') {
            $query->where('event', 'updated');
        } elseif ($this->tab === 'entradas_saidas') {
            $query->where(function ($q) {
                $q->whereIn('event', ['created', 'deleted', 'restored'])
                  ->orWhere('subject_type', MovementHistory::class);
            });
        }

        if (! empty($this->unit_id)) {
            $unitId = (int) $this->unit_id;

            $query->where(function ($q) use ($unitId) {
                $q->where(function ($s) use ($unitId) {
                    $s->where('subject_type', Patient::class)
                      ->whereIn('subject_id', $this->pacientesDaUnidade($unitId));
                })
                ->orWhere(function ($s) use ($unitId) {
                    $s->where('subject_type', Professional::class)
                      ->whereIn('subject_id', $this->profissionaisDaUnidade($unitId));
                })
                ->orWhere(function ($s) use ($unitId) {
                    $s->where('subject_type', MovementHistory::class)
                      ->whereIn('subject_id', MovementHistory::query()
                          ->where(function ($m) use ($unitId) {
                              $m->where('moveable_type', Patient::class)
                                ->whereIn('moveable_id', $this->pacientesDaUnidade($unitId));
                          })
                          ->orWhere(function ($m) use ($unitId) {
                              $m->where('moveable_type', Professional::class)
                                ->whereIn('moveable_id', $this->profissionaisDaUnidade($unitId));
                          })
                          ->select('id'));
                });
            });
        }

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->whereHas('causer', function ($causerQuery) {
                    $causerQuery->where('name', 'like', '%' . $this->search . '%');
                })
                ->orWhere('subject_type', 'like', '%' . $this->search . '%');
            });
        }

        return view('livewire.controles.index', [
            'atividades'   => $query->paginate(15),
            'unidadesFiltro' => Unit::orderBy('name')->get(),
        ]);
    }
}
