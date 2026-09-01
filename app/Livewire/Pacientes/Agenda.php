<?php

namespace App\Livewire\Pacientes;

use App\Models\Patient;
use App\Models\Schedule;
use App\Models\Professional;
use App\Models\Therapy;
use App\Models\ServiceType;
use Livewire\Component;
use Carbon\Carbon;

class Agenda extends Component
{
    public Patient $patient;

    public $isModalOpen = false;
    public $editingScheduleId = null;

    // Profissional "puro" (sem papel elevado) que atende terapia além de ABA pode
    // gerenciar a própria agenda aqui, mas só a própria — isRestrictedProfissional
    // governa o filtro de professional_id e a checagem de posse nas ações de escrita.
    public $isRestrictedProfissional = false;
    public $podeGerenciarAgenda = false;

    public $day_of_week;
    public $start_time;
    public $end_time;
    public $professional_id;
    public $therapy_id;
    public $service_type_id;

    protected $rules = [
        'day_of_week' => 'required|string',
        'start_time' => 'required',
        'end_time' => 'required|after:start_time',
        'professional_id' => 'required|exists:professionals,id',
        'therapy_id' => 'required|exists:therapies,id',
        'service_type_id' => 'required|exists:service_types,id',
    ];

    public function mount(Patient $patient)
    {
        $this->patient = $patient;

        $user = auth()->user();

        $papelOrganizacional = $user->hasAnyRole(['admin', 'manager', 'administrative']);

        // Mesma regra de AgendaProfissionais\Index::mount() — coordinator/supervisor só
        // mantém edição irrestrita (agenda de qualquer paciente) se atender ABA.
        $coordenaAba = $user->hasAnyRole(['coordinator', 'supervisor'])
            && ($user->professional?->atendeAba() ?? false);

        if (! $papelOrganizacional && ! $coordenaAba && $user->hasRole('profissional')) {
            $this->isRestrictedProfissional = true;
            $this->podeGerenciarAgenda = (bool) $user->professional?->atendeTerapiaNaoAba();
        } else {
            $this->podeGerenciarAgenda = true;
        }
    }

    /**
     * SEGURANÇA: os botões de Novo/Editar/Excluir só aparecem no blade pra quem
     * podeGerenciarAgenda, mas nenhuma ação de escrita conferia isso — qualquer
     * profissional autenticado conseguia criar, editar ou excluir horário de
     * QUALQUER paciente via requisição direta ao Livewire, inclusive
     * deleteSchedule() sem checar sequer se o horário era deste paciente.
     *
     * podeGerenciarAgenda é true pra admin|manager|administrative|coordinator|
     * supervisor (irrestrito) OU pra profissional puro que atende terapia além de
     * ABA (só a própria agenda — ver autorizarProfissionalAlvo/autorizarScheduleAlvo).
     */
    private function autorizarEscrita(): void
    {
        if (! $this->podeGerenciarAgenda) {
            abort(403, 'Você não tem permissão para gerenciar a agenda deste paciente.');
        }
    }

    /**
     * Além de podeGerenciarAgenda, profissional restrito só grava um horário cujo
     * professional_id é o dele mesmo — sem isso, um profissional multi-terapia
     * conseguiria atribuir/editar o horário de um COLEGA nesta mesma tela.
     */
    private function autorizarProfissionalAlvo(): void
    {
        if (! $this->isRestrictedProfissional) {
            return;
        }

        $user = auth()->user();
        if (! $user->professional || (int) $this->professional_id !== (int) $user->professional->id) {
            abort(403, 'Você só pode gerenciar a própria agenda.');
        }
    }

    /**
     * Mesma checagem de posse, mas contra um Schedule já existente (editar/excluir).
     */
    private function autorizarScheduleAlvo(Schedule $schedule): void
    {
        if (! $this->isRestrictedProfissional) {
            return;
        }

        $user = auth()->user();
        if (! $user->professional || (int) $schedule->professional_id !== (int) $user->professional->id) {
            abort(403, 'Você só pode gerenciar a própria agenda.');
        }
    }

    public function openModal()
    {
        $this->autorizarEscrita();

        $this->resetValidation();
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function updatedTherapyId()
    {
        $this->professional_id = ''; 
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->editingScheduleId = null;
        $this->day_of_week = '';
        $this->start_time = '';
        $this->end_time = '';
        $this->professional_id = '';
        $this->therapy_id = '';
        $this->service_type_id = '';
    }

    public function editSchedule(Schedule $schedule)
    {
        $this->autorizarEscrita();

        if ((int) $schedule->patient_id !== (int) $this->patient->id) {
            abort(404);
        }

        $this->autorizarScheduleAlvo($schedule);

        $this->resetValidation();

        $this->editingScheduleId = $schedule->id;
        $this->day_of_week = $schedule->day_of_week;
        $this->start_time = Carbon::parse($schedule->start_time)->format('H:i');
        $this->end_time = Carbon::parse($schedule->end_time)->format('H:i');
        $this->professional_id = $schedule->professional_id;
        $this->therapy_id = $schedule->therapy_id;
        $this->service_type_id = $schedule->service_type_id;

        $this->isModalOpen = true;
    }

    public function saveSchedule()
    {
        $this->autorizarEscrita();

        // Ao editar, confere que o horário sendo alterado pertence mesmo a este
        // paciente — patient_id vem fixo de $this->patient no $data abaixo, mas sem
        // isso o editingScheduleId poderia apontar pro horário de outro paciente.
        if ($this->editingScheduleId) {
            $scheduleExistente = Schedule::find($this->editingScheduleId);

            if ((int) $scheduleExistente?->patient_id !== (int) $this->patient->id) {
                abort(404);
            }

            // Profissional restrito não pode "adotar" o horário de um colega editando-o.
            $this->autorizarScheduleAlvo($scheduleExistente);
        }

        $this->validate();

        // Profissional restrito só grava com professional_id igual ao dele mesmo —
        // sem isso, ele atribuiria um horário novo a um colega por esta mesma tela.
        $this->autorizarProfissionalAlvo();

        $conflito = Schedule::where('professional_id', $this->professional_id)
            ->where('day_of_week', $this->day_of_week)
            ->when($this->editingScheduleId, function($query) {
                return $query->where('id', '!=', $this->editingScheduleId);
            })
            ->where(function ($query) {
                $query->where('start_time', '<', $this->end_time)
                      ->where('end_time', '>', $this->start_time);
            })
            ->first();

        if ($conflito) {
            if ($conflito->is_blocked) {
                $this->addError('professional_id', 'Erro: O profissional marcou este período como NÃO DISPONÍVEL.');
            } else {
                $this->addError('start_time', 'Erro: O profissional já possui outro paciente agendado neste horário.');
            }
            return;
        }

        $data = [
            'day_of_week' => $this->day_of_week,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'patient_id' => $this->patient->id,
            'professional_id' => $this->professional_id,
            'therapy_id' => $this->therapy_id,
            'service_type_id' => $this->service_type_id,
        ];

        if ($this->editingScheduleId) {
            Schedule::find($this->editingScheduleId)->update($data);
            session()->flash('message', 'Horário atualizado com sucesso!');
        } else {
            Schedule::create($data);
            session()->flash('message', 'Horário adicionado com sucesso!');
        }

        $this->closeModal();
    }

    public function deleteSchedule($id)
    {
        $this->autorizarEscrita();

        $schedule = Schedule::findOrFail($id);

        if ((int) $schedule->patient_id !== (int) $this->patient->id) {
            abort(404);
        }

        $this->autorizarScheduleAlvo($schedule);

        $schedule->delete();
        session()->flash('message', 'Horário removido com sucesso!');
    }

    /**
     * Usado no blade pra decidir se mostra editar/excluir em cada card — irrestrito
     * vê em todos; multi-terapia só nos horários que já são dele mesmo.
     */
    public function podeEditarSchedule(Schedule $schedule): bool
    {
        if (! $this->podeGerenciarAgenda) {
            return false;
        }

        if (! $this->isRestrictedProfissional) {
            return true;
        }

        $user = auth()->user();

        return $user->professional && (int) $schedule->professional_id === (int) $user->professional->id;
    }

    public function render()
    {
        $schedules = $this->patient->schedules()
            ->with(['professional', 'therapy', 'serviceType'])
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week');

        $professionalsQuery = Professional::query();

        if (!empty($this->therapy_id)) {
            $professionalsQuery->whereHas('therapies', function($q) {
                $q->where('therapies.id', $this->therapy_id);
            });
        } else {
            $professionalsQuery->where('id', '<', 0);
        }

        // Multi-terapia só atribui horário a si mesmo — nem chega a ver colega na lista.
        if ($this->isRestrictedProfissional) {
            $professionalId = auth()->user()->professional?->id ?? 0;
            $professionalsQuery->where('id', $professionalId);
        }

        return view('livewire.pacientes.agenda', [
            'schedulesGrouped' => $schedules,
            'professionals' => $professionalsQuery->orderBy('name')->get(), 
            'therapies' => Therapy::orderBy('name')->get(),        
            'serviceTypes' => ServiceType::orderBy('name')->get(),  
        ]);
    }
}