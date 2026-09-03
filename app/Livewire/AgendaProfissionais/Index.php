<?php

namespace App\Livewire\AgendaProfissionais;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
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

    // SEGURANÇA (IDOR): #[Locked] nos dois — sem isso, um profissional restrito
    // conseguia $wire.set('isRestricted', false) numa requisição forjada, e
    // autorizarProfissionalAlvo()/autorizarSchedule() (que só checam quando
    // isRestricted é true) parariam de barrar nada — abrindo edição da agenda de
    // QUALQUER profissional só adulterando também professional_id. Mesma classe de
    // bug do patientIdsVinculados em TerapiasRealizadas\Index (ver CLAUDE.md).
    #[Locked]
    public $isRestricted = false;
    // Profissional restrito que atende alguma terapia além de ABA pode editar a
    // própria agenda (Agendar Paciente / editar / excluir); ABA puro fica só na
    // visualização. Sempre true fora do caso restrito (admin/manager/... já editam tudo).
    #[Locked]
    public $podeEditarAgendaPaciente = true;
    public $isBlockModalOpen = false;
    public $block_day = '';
    public $block_start_time = '';
    public $block_end_time = '';
    public $block_whole_day = false;

    public $isScheduleModalOpen = false;
    public $editingScheduleId = null;
    public $patient_id = '';
    public $schedule_day = '';
    public $schedule_start_time = '';
    public $schedule_end_time = '';
    public $schedule_therapy_id = '';
    public $schedule_service_type_id = '';

    public function mount()
    {
        $user = auth()->user();

        $papelOrganizacional = $user->hasAnyRole(['admin', 'manager', 'administrative']);

        // coordinator/supervisor mantém edição irrestrita (qualquer profissional) só
        // pelo papel Spatie, sem depender de atender ABA. Reverte a checagem de
        // atendeAba() de 31/08/2026 — o usuário decidiu que o papel de coordenação é
        // prioritário: quem é coordenador/supervisor gerencia a agenda de qualquer
        // profissional independente da própria especialidade. Motivou o caso de Willian
        // da Silva Nunes (coordenador de Psicomotricidade, não atende ABA) sem
        // conseguir mexer na agenda de ninguém, nem a própria.
        $ehCoordenacao = $user->hasAnyRole(['coordinator', 'supervisor']);

        if (! $papelOrganizacional && ! $ehCoordenacao && $user->hasRole('profissional')) {
            $this->isRestricted = true;
            $this->podeEditarAgendaPaciente = false;
            if ($user->professional) {
                $this->professional_id = $user->professional->id;
                $this->podeEditarAgendaPaciente = $user->professional->atendeTerapiaNaoAba();
            }
        }
    }

    /**
     * SEGURANÇA (IDOR): isRestricted/professional_id no mount() só define o valor
     * INICIAL do formulário — o navegador continua livre pra mandar qualquer
     * professional_id na ação do Livewire. Sem esta checagem, um profissional comum
     * conseguia criar, editar ou excluir horário/bloqueio na agenda de QUALQUER outro
     * profissional só trocando esse campo (ou o ID do agendamento) na requisição,
     * apesar da tela nunca mostrar essa opção pra ele.
     */
    private function autorizarProfissionalAlvo(): void
    {
        $user = auth()->user();

        if (! $this->isRestricted) {
            return;
        }

        if (! $user->professional || (int) $this->professional_id !== (int) $user->professional->id) {
            abort(403, 'Você só pode gerenciar a própria agenda.');
        }
    }

    /**
     * Além da posse, agendamento de PACIENTE (não bloqueio de horário) só pode ser
     * criado/editado/excluído por profissional restrito se ele atender terapia além
     * de ABA (podeEditarAgendaPaciente) — pedido do usuário: ABA puro só visualiza,
     * multi-terapia mexe na própria agenda. "Notificar Horário Indisponível" (bloqueio)
     * continua liberado pra todo restrito, independente de terapia — não mudou.
     */
    private function autorizarEdicaoAgendaPaciente(): void
    {
        if ($this->isRestricted && ! $this->podeEditarAgendaPaciente) {
            abort(403, 'Você não tem permissão para editar agendamentos de pacientes.');
        }
    }

    /**
     * SEGURANÇA (IDOR): confere que o Schedule pertence ao profissional que o usuário
     * tem permissão de gerenciar, antes de editar/excluir por ID.
     */
    private function autorizarSchedule(int $scheduleId): Schedule
    {
        $schedule = Schedule::findOrFail($scheduleId);

        $user = auth()->user();
        if ($this->isRestricted
            && (! $user->professional || (int) $schedule->professional_id !== (int) $user->professional->id)) {
            abort(403, 'Você só pode gerenciar a própria agenda.');
        }

        return $schedule;
    }

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
        $this->autorizarProfissionalAlvo();

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
        $schedule = $this->autorizarSchedule($scheduleId);

        if ($schedule->is_blocked) {
            $schedule->delete();
            session()->flash('message', 'Bloqueio removido e horário liberado!');
        }
    }

    public function openScheduleModal($dayNum = null, $time = null)
    {
        if (!$this->professional_id) {
            session()->flash('error', 'Selecione um profissional primeiro.');
            return;
        }

        $this->autorizarProfissionalAlvo();
        $this->autorizarEdicaoAgendaPaciente();

        $this->resetValidation();
        $this->editingScheduleId = null;
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
        $this->autorizarSchedule($schedule->id);

        if (! $schedule->is_blocked) {
            $this->autorizarEdicaoAgendaPaciente();
        }

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
        $schedule = $this->autorizarSchedule($id);

        if (! $schedule->is_blocked) {
            $this->autorizarEdicaoAgendaPaciente();
        }

        $schedule->delete();
        session()->flash('message', 'Agendamento excluído com sucesso!');
    }

    public function saveSchedule()
    {
        $this->autorizarProfissionalAlvo();
        $this->autorizarEdicaoAgendaPaciente();

        if ($this->editingScheduleId) {
            $this->autorizarSchedule($this->editingScheduleId);
        }

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

        if ($this->editingScheduleId) {
            Schedule::find($this->editingScheduleId)->update($data);
            session()->flash('message', 'Agendamento atualizado com sucesso!');
        } else {
            Schedule::create($data);
            session()->flash('message', 'Agendamento cadastrado com sucesso!');
        }

        $this->closeScheduleModal();
    }

    public function getAgendaProperty()
    {
        $vazio = [1 => [], 2 => [], 3 => [], 4 => [], 5 => []];
        $agenda = ['DiaInteiro' => $vazio, 'Horarios' => []];

        for ($i = 7; $i <= 18; $i++) {
            $horaStr = str_pad($i, 2, '0', STR_PAD_LEFT) . ':00';
            $agenda['Horarios'][$horaStr] = [1 => [], 2 => [], 3 => [], 4 => [], 5 => []];
        }

        if (!$this->professional_id) return $agenda;

        // withTrashed() no patient: sem isso, um horário de paciente com saída
        // registrada (soft delete) mostrava "Paciente Removido" genérico — pedido do
        // usuário pra continuar mostrando quem era, só sinalizando com um badge.
        $horarios = Schedule::with(['patient' => fn ($q) => $q->withTrashed(), 'therapy', 'serviceType'])
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