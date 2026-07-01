<?php

namespace App\Livewire\Coordenacao\Cronograma;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\PatientService;
use App\Models\Appointment;
use App\Models\Visit;
use App\Models\User;
use App\Models\ServiceType;
use App\Models\Therapy;
use App\Models\Professional;
use App\Models\Unit;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    // Filtros
    public $ambiente_id = '';
    public $unidade_id = '';
    public $profissional_id = '';
    public $status_coordenacao = '';
    public $status_supervisao = '';
    public $search = '';
    public $isHistoryModalOpen = false;
    public $historyData = [
        'paciente' => '',
        'coordenacao' => [],
        'supervisao' => []
    ];

    public function updatedSearch() { $this->resetPage(); }
    public function aplicarFiltros() { $this->resetPage(); }
    public function limparFiltros()
    {
        $this->reset(['ambiente_id', 'unidade_id', 'profissional_id', 'status_coordenacao', 'status_supervisao', 'search']);
        $this->resetPage();
    }


    private function calcularMetricas(PatientService $record)
    {
        $latestAppt = Appointment::where('patient_id', $record->patient_id)
            ->where('service_type_id', $record->service_type_id)
            ->whereHas('therapy', fn($q) => $q->whereIn('name', ['ABA', 'DENVER']))
            ->latest('appointment_date')
            ->first();

        $terapiaNome = $latestAppt ? $latestAppt->therapy->name : 'N/A';
        $therapyId = $latestAppt ? $latestAppt->therapy_id : null;

        return [
            'terapia' => $terapiaNome,
            'coord' => $this->getVisitaStatus($record, $therapyId, VisitType::Coordination, 10, $record->coordinator_id),
            'superv' => $this->getVisitaStatus($record, $therapyId, VisitType::Supervision, 20, $record->supervisor_id),
        ];
    }

    private function getVisitaStatus(PatientService $record, $therapyId, VisitType $type, $meta, $profissionalVinculado)
    {
        if (!$therapyId) return ['badge' => ['label' => 'Sem registros', 'color' => 'bg-gray-100 text-gray-800'], 'ultima_data' => '', 'ultima_prof' => ''];

        $lastVisit = Visit::with('professional')
            ->where('patient_id', $record->patient_id)
            ->where(fn($q) => $q->where('service_type_id', $record->service_type_id)->orWhereNull('service_type_id'))
            ->where('type', $type->value)
            ->where('status', VisitStatus::Completed->value)
            ->where('therapy_id', $therapyId)
            ->latest('happened_at')->first();

        $startDate = $lastVisit ? $lastVisit->happened_at : null;

        $query = Appointment::where('patient_id', $record->patient_id)
            ->where('service_type_id', $record->service_type_id)
            ->where('appointment_date', '<=', Carbon::today())
            ->where('therapy_id', $therapyId);

        if ($startDate) $query->where('appointment_date', '>', $startDate);

        $daysCount = $query->select(DB::raw('DATE(appointment_date) as date'))->groupBy('date')->get()->count();

        if ($daysCount > 0 && empty($profissionalVinculado)) {
            $cargo = $type === VisitType::Coordination ? 'coordenador' : 'supervisor';
            $badge = ['label' => "🚨 Sem {$cargo} ({$daysCount}/{$meta} dias)", 'color' => 'bg-red-100 text-red-800 border-red-200'];
        } else {
            $hasPending = Visit::where('patient_id', $record->patient_id)
                ->where(fn($q) => $q->where('service_type_id', $record->service_type_id)->orWhereNull('service_type_id'))
                ->where('type', $type->value)
                ->where('status', VisitStatus::Pending->value)
                ->where('therapy_id', $therapyId)->exists();

            if ($hasPending) {
                $badge = ['label' => '⚠️ Visita Pendente', 'color' => 'bg-yellow-100 text-yellow-800 border-yellow-200'];
            } elseif ($daysCount === 0) {
                $badge = ['label' => '✅ Em dia (0 dias)', 'color' => 'bg-green-100 text-green-800 border-green-200'];
            } else {
                $badge = ['label' => "⏳ {$daysCount} / {$meta} dias", 'color' => 'bg-blue-100 text-blue-800 border-blue-200'];
            }
        }

        if ($lastVisit && $lastVisit->happened_at) {
            $data = Carbon::parse($lastVisit->happened_at)->format('d/m/Y');
            $nome = $lastVisit->professional ? implode(' ', array_slice(explode(' ', $lastVisit->professional->name), 0, 2)) : 'Desconhecido';
            $ultimaString = "Última: {$data} ({$nome})";
        } else {
            $ultimaString = 'Nenhuma visita registrada';
        }

        return ['badge' => $badge, 'ultima_string' => $ultimaString];
    }

    private function aplicarFiltroDeStatus($query, $statusValorSimbolico, VisitType $typeEnum)
    {
        if (empty($statusValorSimbolico)) return;

        $type = $typeEnum->value;
        $completedStatus = VisitStatus::Completed->value;
        $pendingStatus = VisitStatus::Pending->value;

        $meta = ($typeEnum === VisitType::Coordination) ? 10 : 20;
        $colunaProfissional = ($typeEnum === VisitType::Coordination) ? 'coordinator_id' : 'supervisor_id';
        $chaveSemProfissional = ($typeEnum === VisitType::Coordination) ? 'sem_coordenador' : 'sem_supervisor';

        $daysCountSql = "(SELECT COUNT(DISTINCT DATE(appointments.appointment_date)) 
            FROM appointments 
            JOIN therapies ON appointments.therapy_id = therapies.id
            WHERE appointments.patient_id = patient_services.patient_id
              AND appointments.service_type_id = patient_services.service_type_id
              AND appointments.appointment_date <= CURRENT_DATE
              AND therapies.name IN ('ABA', 'DENVER')
              AND appointments.appointment_date > COALESCE((
                  SELECT visits.happened_at FROM visits
                  JOIN therapies vt ON visits.therapy_id = vt.id
                  WHERE visits.patient_id = patient_services.patient_id
                    AND (visits.service_type_id = patient_services.service_type_id OR visits.service_type_id IS NULL)
                    AND visits.type = '{$type}' AND visits.status = '{$completedStatus}'
                    AND vt.name = therapies.name
                  ORDER BY visits.happened_at DESC LIMIT 1
              ), '2000-01-01'))";

        match ($statusValorSimbolico) {
            
            $chaveSemProfissional => $query->whereNull($colunaProfissional)->whereRaw("{$daysCountSql} > 0"),
            
            'pendente' => $query->whereExists(function ($sub) use ($type, $pendingStatus) {
                $sub->select(DB::raw(1))->from('visits')
                    ->join('therapies', 'visits.therapy_id', '=', 'therapies.id')
                    ->whereColumn('visits.patient_id', 'patient_services.patient_id')
                    ->whereRaw('(visits.service_type_id = patient_services.service_type_id OR visits.service_type_id IS NULL)')
                    ->where('visits.type', $type)
                    ->where('visits.status', $pendingStatus) 
                    ->whereIn('therapies.name', ['ABA', 'DENVER']);
            }),
            
            'em_dia' => $query->whereRaw("{$daysCountSql} = 0")
                ->whereNotExists(function ($sub) use ($type, $pendingStatus) {
                    $sub->select(DB::raw(1))->from('visits')
                        ->join('therapies', 'visits.therapy_id', '=', 'therapies.id')
                        ->whereColumn('visits.patient_id', 'patient_services.patient_id')
                        ->whereRaw('(visits.service_type_id = patient_services.service_type_id OR visits.service_type_id IS NULL)')
                        ->where('visits.type', $type)
                        ->where('visits.status', $pendingStatus) 
                        ->whereIn('therapies.name', ['ABA', 'DENVER']);
                }),
                
            'em_andamento' => $query->whereRaw("{$daysCountSql} > 0")
                ->whereRaw("{$daysCountSql} < {$meta}")
                ->whereNotNull($colunaProfissional)
                ->whereNotExists(function ($sub) use ($type, $pendingStatus) {
                    $sub->select(DB::raw(1))->from('visits')
                        ->join('therapies', 'visits.therapy_id', '=', 'therapies.id')
                        ->whereColumn('visits.patient_id', 'patient_services.patient_id')
                        ->whereRaw('(visits.service_type_id = patient_services.service_type_id OR visits.service_type_id IS NULL)')
                        ->where('visits.type', $type)
                        ->where('visits.status', $pendingStatus)
                        ->whereIn('therapies.name', ['ABA', 'DENVER']);
                }),
                
            default => $query,
        };
    }

    public function openHistory($patientId, $serviceTypeId)
    {
        $patient = \App\Models\Patient::find($patientId);
        
        // Busca TODAS as visitas REALIZADAS deste paciente, neste ambiente, de ABA ou DENVER
        $visits = Visit::with(['professional', 'therapy', 'serviceType'])
            ->where('patient_id', $patientId)
            ->where(function($q) use ($serviceTypeId) {
                if ($serviceTypeId) {
                    $q->where('service_type_id', $serviceTypeId)->orWhereNull('service_type_id');
                }
            })
            ->whereIn('type', [VisitType::Coordination->value, VisitType::Supervision->value])
            ->where('status', VisitStatus::Completed->value) // Traz apenas o que já foi feito
            ->whereHas('therapy', fn($q) => $q->whereIn('name', ['ABA', 'DENVER']))
            ->orderBy('happened_at', 'desc')
            ->get();

        $this->historyData['paciente'] = $patient ? $patient->name : 'Paciente Desconhecido';
        
        // Separa as visitas nas duas colunas do modal
        $this->historyData['coordenacao'] = $visits->where('type', VisitType::Coordination->value);
        $this->historyData['supervisao'] = $visits->where('type', VisitType::Supervision->value);

        $this->isHistoryModalOpen = true;
    }

    public function closeHistoryModal()
    {
        $this->isHistoryModalOpen = false;
        $this->historyData = ['paciente' => '', 'coordenacao' => [], 'supervisao' => []];
    }

    public function render()
    {
        $query = PatientService::with(['patient.unit', 'serviceType', 'coordinator', 'supervisor'])
            ->whereHas('patient', function ($q) {
                if ($this->unidade_id) {
                    $q->where('unit_id', $this->unidade_id);
                }
                if ($this->search) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                }
            })
            ->whereExists(function ($subQuery) {
                $subQuery->select(DB::raw(1))->from('appointments')
                    ->join('therapies', 'appointments.therapy_id', '=', 'therapies.id')
                    ->whereColumn('appointments.patient_id', 'patient_services.patient_id')
                    ->whereColumn('appointments.service_type_id', 'patient_services.service_type_id')
                    ->whereIn('therapies.name', ['ABA', 'DENVER']);
            });

        if ($this->ambiente_id) {
            $query->where('service_type_id', $this->ambiente_id);
        }

        if ($this->profissional_id) {
            $query->where(function ($q) {
                $q->where('patient_services.coordinator_id', $this->profissional_id)
                  ->orWhere('patient_services.supervisor_id', $this->profissional_id);
            });
        }

        $this->aplicarFiltroDeStatus($query, $this->status_coordenacao, VisitType::Coordination);
        $this->aplicarFiltroDeStatus($query, $this->status_supervisao, VisitType::Supervision);

        $paginatedResults = $query->paginate(15);

        $linhasFormatadas = $paginatedResults->getCollection()->map(function ($record) {
            $metricas = $this->calcularMetricas($record);
            return (object) [
                'paciente' => $record->patient,
                'ambiente' => $record->serviceType,
                'terapia'  => $metricas['terapia'],
                'coord'    => (object) $metricas['coord'],
                'superv'   => (object) $metricas['superv'],
            ];
        });

        $paginatedResults->setCollection($linhasFormatadas);

        $allowedUnits = auth()->user()->getAllowedUnitIds();

        $unidadesQuery = Unit::query();
        if ($allowedUnits !== null) {
            if (empty($allowedUnits)) {
                $unidadesQuery->whereRaw('1 = 0');
            } else {
                $unidadesQuery->whereIn('id', $allowedUnits);
            }
        }

        $profissionaisQuery = Professional::whereIn('role', ['coordinator', 'supervisor']);
        if ($allowedUnits !== null) {
            if (empty($allowedUnits)) {
                $profissionaisQuery->whereRaw('1 = 0');
            } else {
                $profissionaisQuery->whereHas('units', function($q) use ($allowedUnits) {
                    $q->whereIn('unit_id', $allowedUnits);
                });
            }
        }

        return view('livewire.coordenacao.cronograma.index', [
            'linhas' => $paginatedResults,
            'ambientes' => ServiceType::all(),
            'unidades' => $unidadesQuery->get(),
            'profissionais' => $profissionaisQuery->get(),
        ]);
    }
}