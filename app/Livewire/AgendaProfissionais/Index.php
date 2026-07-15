<?php

namespace App\Livewire\AgendaProfissionais;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Professional;
use App\Models\Schedule;
use App\Models\Patient;
use App\Models\Therapy;
use App\Models\ServiceType;
use Carbon\Carbon;

#[Layout('layouts.app')]
class Index extends Component
{
    public $professional_id = '';
    public $isRestricted = false; 

    // ==========================================
    // VARIÁVEIS DO MODAL DE BLOQUEIO
    // ==========================================
    public $isBlockModalOpen = false;
    public $block_day = '';
    public $block_start_time = '';
    public $block_end_time = '';
    public $block_whole_day = false;

    // ==========================================
    // VARIÁVEIS DO AGENDAMENTO DE PACIENTE
    // ==========================================
    public $isScheduleModalOpen = false;
    public $editingScheduleId = null; // <-- NOVA VARIÁVEL PARA EDIÇÃO
    public $patient_id = '';
    public $schedule_day = '';
    public $schedule_start_time = '';
    public $schedule_end_time = '';
    public $schedule_therapy_id = '';
    public $schedule_service_type_id = '';

    public function mount()
    {
        $user = auth()->user();
        
        if (!$user->hasAnyRole(['admin', 'manager', 'administrative']) && $user->hasRole('profissional')) {
            $this->isRestricted = true;
            if ($user->professional) {
                $this->professional_id = $user->professional->id;
            }
        }
    }

    // ==========================================
    // FUNÇÕES DE BLOQUEIO
    // ==========================================
    public function openBlockModal()
    {
        if (!$this->professional_id) {
            session()->flash('error', 'Selecione um profissional primeiro.');
            return;
        }
        
        $this->resetValidation();
        $this->block_day = '';
        $this->block_start_time = '';
        $this->block_end_time = '';
        $this->block_whole_day = false;
        $this->isBlockModalOpen = true;
    }

    public function closeBlockModal()
    {
        $this->isBlockModalOpen = false;
    }

    public function saveBlock()
    {
        $rules = [
            'professional_id' => 'required|exists:professionals,id',
            'block_day' => 'required|string',
        ];

        if (!$this->block_whole_day) {
            $rules['block_start_time'] = 'required';
            $rules['block_end_time'] = 'required|after:block_start_time';
        }

        $this->validate($rules, [
            'block_end_time.after' => 'O horário final deve ser depois do inicial.',
            'block_day.required' => 'Selecione o dia da semana.',
            'block_start_time.required' => 'Defina a hora inicial.',
        ]);

        $startTime = $this->block_whole_day ? '00:00:00' : $this->block_start_time;
        $endTime = $this->block_whole_day ? '23:59:59' : $this->block_end_time;

        $conflito = Schedule::where('professional_id', $this->professional_id)
            ->where('day_of_week', $this->block_day)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
            })
            ->exists();

        if ($conflito) {
            $this->addError('block_time', 'Não é possível bloquear. Já existe um paciente ou bloqueio neste intervalo.');
            return;
        }

        Schedule::create([
            'professional_id' => $this->professional_id,
            'day_of_week' => $this->block_day,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'is_blocked' => true,
        ]);

        session()->flash('message', 'Horário bloqueado com sucesso!');
        $this->closeBlockModal();
    }

    public function removeBlock($scheduleId)
    {
        $schedule = Schedule::find($scheduleId);
        if ($schedule && $schedule->is_blocked) {
            $schedule->delete();
            session()->flash('message', 'Bloqueio removido e horário liberado!');
        }
    }

    // ==========================================
    // FUNÇÕES DE AGENDAMENTO E EDIÇÃO (NOVIDADES AQUI)
    // ==========================================
    public function openScheduleModal($dayNum = null, $time = null)
    {
        if (!$this->professional_id) {
            session()->flash('error', 'Selecione um profissional primeiro.');
            return;
        }

        $this->resetValidation();
        $this->editingScheduleId = null; // Garante que é criação nova
        $this->patient_id = '';
        $this->schedule_therapy_id = '';
        $this->schedule_service_type_id = '';

        $daysMap = [1 => 'segunda', 2 => 'terca', 3 => 'quarta', 4 => 'quinta', 5 => 'sexta'];
        $this->schedule_day = ($dayNum && isset($daysMap[$dayNum])) ? $daysMap[$dayNum] : '';

        if ($time) {
            $this->schedule_start_time = $time;
            $this->schedule_end_time = Carbon::parse($time)->addMinutes(40)->format('H:i');
        } else {
            $this->schedule_start_time = '';
            $this->schedule_end_time = '';
        }

        $this->isScheduleModalOpen = true;
    }

    public function editSchedule(Schedule $schedule)
    {
        $this->resetValidation();
        
        $this->editingScheduleId = $schedule->id;
        $this->patient_id = $schedule->patient_id;
        $this->schedule_day = $schedule->day_of_week;
        $this->schedule_start_time = Carbon::parse($schedule->start_time)->format('H:i');
        $this->schedule_end_time = Carbon::parse($schedule->end_time)->format('H:i');
        $this->schedule_therapy_id = $schedule->therapy_id;
        $this->schedule_service_type_id = $schedule->service_type_id;

        $this->isScheduleModalOpen = true;
    }

    public function closeScheduleModal()
    {
        $this->isScheduleModalOpen = false;
        $this->editingScheduleId = null;
    }

    public function deleteSchedule($id)
    {
        $schedule = Schedule::find($id);
        if ($schedule) {
            $schedule->delete();
            session()->flash('message', 'Agendamento excluído com sucesso!');
        }
    }

    public function saveSchedule()
    {
        $this->validate([
            'patient_id' => 'required|exists:patients,id',
            'schedule_day' => 'required|string',
            'schedule_start_time' => 'required',
            'schedule_end_time' => 'required|after:schedule_start_time',
            'schedule_therapy_id' => 'required|exists:therapies,id',
            'schedule_service_type_id' => 'required|exists:service_types,id',
        ], [
            'schedule_end_time.after' => 'O horário de término deve ser após o início.',
            'patient_id.required' => 'Selecione o paciente.',
            'schedule_day.required' => 'Selecione o dia da semana.',
            'schedule_start_time.required' => 'Informe a hora de início.',
            'schedule_end_time.required' => 'Informe a hora de término.',
            'schedule_therapy_id.required' => 'Selecione a terapia.',
            'schedule_service_type_id.required' => 'Selecione o tipo de atendimento.',
        ]);

        // Blindagem total contra choques (ignorando o próprio ID na hora de editar)
        $conflito = Schedule::where('professional_id', $this->professional_id)
            ->where('day_of_week', $this->schedule_day)
            ->when($this->editingScheduleId, function($query) {
                return $query->where('id', '!=', $this->editingScheduleId);
            })
            ->where(function ($query) {
                $query->where('start_time', '<', $this->schedule_end_time)
                      ->where('end_time', '>', $this->schedule_start_time);
            })
            ->first();

        if ($conflito) {
            if ($conflito->is_blocked) {
                $this->addError('schedule_time', 'Não é possível agendar. O profissional está indisponível neste período.');
            } else {
                $this->addError('schedule_time', 'Não é possível agendar. Já existe outro paciente neste horário.');
            }
            return;
        }

        $data = [
            'professional_id' => $this->professional_id,
            'patient_id' => $this->patient_id,
            'day_of_week' => $this->schedule_day,
            'start_time' => $this->schedule_start_time,
            'end_time' => $this->schedule_end_time,
            'therapy_id' => $this->schedule_therapy_id,
            'service_type_id' => $this->schedule_service_type_id,
            'is_blocked' => false,
        ];

        // Decide se é um Update ou Create
        if ($this->editingScheduleId) {
            Schedule::find($this->editingScheduleId)->update($data);
            session()->flash('message', 'Agendamento atualizado com sucesso!');
        } else {
            Schedule::create($data);
            session()->flash('message', 'Agendamento cadastrado com sucesso!');
        }

        $this->closeScheduleModal();
    }

    // ==========================================
    // RENDERIZAÇÃO DA AGENDA E DIAS
    // ==========================================
    public function getAgendaProperty()
    {
        $vazio = [1 => [], 2 => [], 3 => [], 4 => [], 5 => []];
        $agenda = ['DiaInteiro' => $vazio, 'Horarios' => []];

        for ($i = 7; $i <= 18; $i++) {
            $horaStr = str_pad($i, 2, '0', STR_PAD_LEFT) . ':00';
            $agenda['Horarios'][$horaStr] = [1 => [], 2 => [], 3 => [], 4 => [], 5 => []];
        }

        if (!$this->professional_id) return $agenda;

        $horarios = Schedule::with(['patient', 'therapy', 'serviceType'])
            ->where('professional_id', $this->professional_id)
            ->orderBy('start_time')
            ->get();

        foreach ($horarios as $horario) {
            $horaInicio = Carbon::parse($horario->start_time);
            $horaFim = Carbon::parse($horario->end_time);
            $diaBanco = (string) $horario->day_of_week;
            $diaNumerico = match(strtolower(trim($diaBanco))) {
                'segunda' => 1, 'terca', 'terça' => 2, 'quarta' => 3, 'quinta' => 4, 'sexta' => 5, default => 1, 
            };

            if ($horario->is_blocked && $horaInicio->format('H:i') === '00:00' && $horaFim->format('H:i') === '23:59') {
                $agenda['DiaInteiro'][$diaNumerico][] = $horario;
            } else {
                $horaFormatada = $horaInicio->format('H') . ':00';
                if (!isset($agenda['Horarios'][$horaFormatada])) {
                    $agenda['Horarios'][$horaFormatada] = [1 => [], 2 => [], 3 => [], 4 => [], 5 => []];
                }
                $agenda['Horarios'][$horaFormatada][$diaNumerico][] = $horario;
            }
        }

        ksort($agenda['Horarios']);
        return $agenda;
    }

    public function render()
    {
        $user = auth()->user();
        if ($this->isRestricted && $user->professional) {
            $profissionais = Professional::where('id', $user->professional->id)->get();
        } else {
            $allowedUnits = $user->getAllowedUnitIds();
            $profissionais = Professional::orderBy('name')
                ->when($allowedUnits !== null, function ($q) use ($allowedUnits) {
                    $q->whereHas('units', function ($query) use ($allowedUnits) {
                        $query->whereIn('units.id', $allowedUnits);
                    });
                })->get();
        }

        return view('livewire.agenda-profissionais.index', [
            'profissionais' => $profissionais,
            'agenda' => $this->agenda,
            'patients' => Patient::orderBy('name')->get(),
            'therapies' => Therapy::orderBy('name')->get(),
            'serviceTypes' => ServiceType::all(),
            'diasDaSemana' => [1 => 'SEG', 2 => 'TER', 3 => 'QUA', 4 => 'QUI', 5 => 'SEX']
        ]);
    }
}