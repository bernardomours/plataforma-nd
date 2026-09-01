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
use App\Models\Schedule;
use App\Models\Therapy;
use App\Models\ServiceType;
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

    /**
     * Schedule não guarda o próprio patient_id em properties de forma direta pra unidade
     * (isso é resolvido pelo paciente vinculado), então busca todo Schedule cujo
     * patient_id caiu na unidade filtrada — inclusive de paciente já com saída.
     */
    private function agendasDaUnidade(int $unitId)
    {
        return Schedule::query()
            ->whereIn('patient_id', $this->pacientesDaUnidade($unitId))
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

        if ($subject instanceof Schedule) {
            return $subject->patient?->unit?->city ?? '—';
        }

        // Fallback: registro já excluído do banco (Schedule inclusive, já que não tem
        // soft delete) — usa o patient_id gravado no snapshot da própria atividade.
        $unitId = data_get($atividade->properties, 'attributes.unit_id')
            ?? data_get($atividade->attribute_changes, 'attributes.unit_id');

        if (! $unitId && $atividade->subject_type === Schedule::class) {
            $patientId = data_get($atividade->properties, 'attributes.patient_id')
                ?? data_get($atividade->properties, 'old.patient_id')
                ?? data_get($atividade->attribute_changes, 'attributes.patient_id')
                ?? data_get($atividade->attribute_changes, 'old.patient_id');

            if ($patientId) {
                return Patient::withoutGlobalScopes()->withTrashed()->find($patientId)?->unit?->city ?? '—';
            }
        }

        if ($unitId) {
            return Unit::find($unitId)?->city ?? '—';
        }

        return '—';
    }

    /**
     * Resolve o rótulo pra exibir um campo da agenda ("Início" em vez de "start_time").
     */
    public function labelCampoAgenda(string $campo): string
    {
        return match ($campo) {
            'day_of_week'      => 'Dia da semana',
            'start_time'       => 'Início',
            'end_time'         => 'Término',
            'patient_id'       => 'Paciente',
            'professional_id'  => 'Profissional',
            'therapy_id'       => 'Terapia',
            'service_type_id'  => 'Ambiente',
            'is_blocked'       => 'Natureza',
            default            => $campo,
        };
    }

    /**
     * Resolve o VALOR de um campo da agenda pra algo legível — os campos de relação
     * gravam só o ID no log, e supervisão precisa do nome, não do número.
     */
    public function formatarValorAgenda(string $campo, $valor): string
    {
        if ($valor === null || $valor === '') {
            return '—';
        }

        return match ($campo) {
            'day_of_week'     => ucfirst((string) $valor),
            'start_time', 'end_time' => \Illuminate\Support\Str::of((string) $valor)->substr(0, 5)->toString(),
            'patient_id'      => Patient::withoutGlobalScopes()->withTrashed()->find($valor)?->name ?? "Paciente #{$valor}",
            'professional_id' => Professional::withTrashed()->find($valor)?->name ?? "Profissional #{$valor}",
            'therapy_id'      => Therapy::find($valor)?->name ?? "Terapia #{$valor}",
            'service_type_id' => ServiceType::find($valor)?->name ?? "Tipo #{$valor}",
            'is_blocked'      => $valor ? 'Bloqueio de horário' : 'Atendimento de paciente',
            default           => (string) $valor,
        };
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
                    Schedule::class        => ['patient.unit'],
                ]);
            },
        ])->latest();

        if ($this->tab === 'atualizacoes') {
            $query->where('event', 'updated');
        } elseif ($this->tab === 'entradas_saidas') {
            // Schedule fica de fora daqui de propósito — criação/exclusão de horário tem
            // aba própria ('agenda'), pra não misturar com entrada/saída de paciente e
            // profissional (badges e motivo/observação são específicos de MovementHistory).
            $query->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->whereIn('event', ['created', 'deleted', 'restored'])
                       ->where('subject_type', '!=', Schedule::class);
                })->orWhere('subject_type', MovementHistory::class);
            });
        } elseif ($this->tab === 'agenda') {
            $query->where('subject_type', Schedule::class);
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
                })
                ->orWhere(function ($s) use ($unitId) {
                    // Schedule não tem soft delete: horário excluído já não existe na
                    // tabela pra resolver a unidade pelo paciente. Em vez de esconder o
                    // registro do filtro (pior que mostrar de mais numa tela de
                    // supervisão), exclusão de agenda aparece independente da unidade.
                    $s->where('subject_type', Schedule::class)
                      ->where(function ($s2) use ($unitId) {
                          $s2->whereIn('subject_id', $this->agendasDaUnidade($unitId))
                             ->orWhere('event', 'deleted');
                      });
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
