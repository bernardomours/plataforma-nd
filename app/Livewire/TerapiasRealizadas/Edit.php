<?php

namespace App\Livewire\TerapiasRealizadas;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Therapy;
use App\Models\ServiceType;
use App\Models\Professional;
use App\Models\Agreement;
use App\Models\Unit;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rule;

#[Layout('layouts.app')]
class Edit extends Component
{
    public $appointmentId;
    
    // Propriedades do Formulário
    public $patient_id;
    public $therapy_id;
    public $service_type_id;
    public $professional_id;
    public $data_rapida = 'outro'; // Padrão 'outro' para exibir a data salva
    public $appointment_date;
    public $check_in;
    public $check_out;
    public $session_number;

    /** Convênio e unidade DO ATENDIMENTO (ver Create.php e a migration correspondente). */
    public $agreement_id = '';
    public $unit_id = '';

    public $showFaturamentoModal = false;

    public function mount($id)
    {
        $appointment = Appointment::findOrFail($id);
        $this->appointmentId = $appointment->id;

        // SEGURANÇA: Valida se o agendamento pertence a uma unidade permitida para o usuário.
        // O withoutGlobalScopes() aqui é intencional: precisamos ler o unit_id REAL do
        // paciente (mesmo de outra unidade / com saída registrada) para poder NEGAR — se
        // aplicássemos o scope viria null e não distinguiríamos "não existe" de "é de outra".
        $this->authorizeAppointmentUnit($appointment->patient_id);

        // Inicialização das propriedades
        $this->patient_id = $appointment->patient_id;
        $this->therapy_id = $appointment->therapy_id;
        $this->service_type_id = $appointment->service_type_id;
        $this->professional_id = $appointment->professional_id;
        $this->appointment_date = $appointment->appointment_date ? \Carbon\Carbon::parse($appointment->appointment_date)->format('Y-m-d') : null;
        
        // Formata os tempos para o padrão H:i (removendo os segundos do banco)
        $this->check_in = $appointment->check_in ? \Carbon\Carbon::parse($appointment->check_in)->format('H:i') : null;
        $this->check_out = $appointment->check_out ? \Carbon\Carbon::parse($appointment->check_out)->format('H:i') : null;
        $this->session_number = $appointment->session_number;

        // Atendimentos anteriores à migration podem estar sem os campos; nesse caso
        // caímos no cadastro atual do paciente, que é o que os relatórios já usavam.
        $this->agreement_id = $appointment->agreement_id ?: '';
        $this->unit_id = $appointment->unit_id ?: '';

        if (! $this->agreement_id || ! $this->unit_id) {
            $this->aplicarPadraoDoPaciente();
        }
    }

    /**
     * SEGURANÇA: sobrescrever convênio/unidade altera dado de faturamento — mesmos papéis
     * que já podem lançar atendimento e editar o cadastro do paciente.
     */
    public function podeAlterarFaturamento(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'manager', 'administrative']);
    }

    public function abrirFaturamentoModal()
    {
        if (! $this->podeAlterarFaturamento()) {
            abort(403, 'Você não tem permissão para alterar convênio ou unidade do atendimento.');
        }

        $this->showFaturamentoModal = true;
    }

    public function fecharFaturamentoModal()
    {
        $this->showFaturamentoModal = false;
    }

    public function restaurarPadraoPaciente()
    {
        $this->aplicarPadraoDoPaciente();
        $this->calculateSessions();
        $this->showFaturamentoModal = false;
    }

    private function aplicarPadraoDoPaciente(): void
    {
        if (empty($this->patient_id)) {
            return;
        }

        $patient = Patient::withoutGlobalScope(SoftDeletingScope::class)
            ->select('id', 'agreement_id', 'unit_id')
            ->find($this->patient_id);

        $this->agreement_id = $patient->agreement_id ?? '';
        $this->unit_id = $patient->unit_id ?? '';
    }

    /**
     * SEGURANÇA (multi-tenant): a unidade gravada tem de estar entre as permitidas.
     */
    private function unidadesPermitidasIds(): array
    {
        $allowed = auth()->user()->getAllowedUnitIds();

        return $allowed === null
            ? Unit::pluck('id')->all()
            : array_map('intval', $allowed);
    }

    /**
     * SEGURANÇA (multi-tenant): a unidade de um Appointment é a unidade do paciente.
     * Centraliza a checagem usada no mount() e no save().
     */
    private function authorizeAppointmentUnit($patientId): void
    {
        $patientUnitId = Patient::withoutGlobalScopes()
            ->whereKey($patientId)
            ->value('unit_id');

        if (! auth()->user()->canAccessUnit($patientUnitId)) {
            abort(403, 'Acesso não autorizado a esta unidade.');
        }
    }

    public function rules()
    {
        return [
            'patient_id' => 'required|exists:patients,id',
            'therapy_id' => 'required|exists:therapies,id',
            'service_type_id' => 'required|exists:service_types,id',
            'professional_id' => 'required|exists:professionals,id',
            'appointment_date' => 'required|date',
            'check_in' => 'required|date_format:H:i',
            'check_out' => 'required|date_format:H:i|after:check_in',
            'session_number' => 'required|integer|min:0',
            // Congelados no atendimento; alterados apenas pelo modal.
            'agreement_id' => 'required|exists:agreements,id',
            'unit_id' => ['required', Rule::in($this->unidadesPermitidasIds())],
        ];
    }

    public function messages()
    {
        return [
            'check_out.after' => 'O Check-out deve ser maior que o Check-in.',
        ];
    }

    public function updatedDataRapida($value)
    {
        if ($value === 'hoje') {
            $this->appointment_date = now()->timezone('America/Fortaleza')->format('Y-m-d');
        } elseif ($value === 'ontem') {
            $this->appointment_date = now()->timezone('America/Fortaleza')->subDay()->format('Y-m-d');
        }
    }

    public function updatedTherapyId()
    {
        $this->professional_id = ''; 
        $this->calculateSessions();
    }

    public function updatedPatientId()
    {
        $this->aplicarPadraoDoPaciente();
        $this->calculateSessions();
    }

    public function updatedAgreementId() { $this->calculateSessions(); }
    public function updatedCheckIn() { $this->calculateSessions(); }
    public function updatedCheckOut() { $this->calculateSessions(); }

    private function calculateSessions()
    {
        if (empty($this->check_in) || empty($this->check_out)) {
            $this->session_number = null;
            return;
        }

        $sessionDuration = 40;

        // A duração da sessão vem do convênio DO ATENDIMENTO, não do cadastro do paciente.
        if (!empty($this->agreement_id) && !empty($this->therapy_id)) {
            $agreement = Agreement::find($this->agreement_id);
            $therapy = Therapy::find($this->therapy_id);

            if ($agreement && $therapy) {
                $isHumana = $agreement->name === 'Humana';
                $isAba = $therapy->name === 'ABA';

                $sessionDuration = $isHumana ? 40 : ($isAba ? 60 : 40);
            }
        }

        $checkInTime = \DateTime::createFromFormat('H:i', $this->check_in);
        $checkOutTime = \DateTime::createFromFormat('H:i', $this->check_out);

        if ($checkInTime && $checkOutTime && $checkOutTime > $checkInTime) {
            $interval = $checkOutTime->diff($checkInTime);
            $minutes = ($interval->h * 60) + $interval->i;
            $this->session_number = (int) max(1, round($minutes / $sessionDuration));
        } else {
            $this->session_number = 0;
        }
    }

    public function save()
    {
        $this->validate();

        $appointment = Appointment::findOrFail($this->appointmentId);

        // SEGURANÇA: re-checa no save() — o Livewire re-hidrata sem executar mount(), e
        // valida também o paciente de DESTINO (o payload pode trocar patient_id).
        $this->authorizeAppointmentUnit($appointment->patient_id);
        $this->authorizeAppointmentUnit($this->patient_id);

        $appointment->update([
            'patient_id' => $this->patient_id,
            'therapy_id' => $this->therapy_id,
            'service_type_id' => $this->service_type_id,
            'professional_id' => $this->professional_id,
            'appointment_date' => $this->appointment_date,
            'check_in' => $this->check_in,
            'check_out' => $this->check_out,
            'session_number' => $this->session_number,
            'agreement_id' => $this->agreement_id,
            'unit_id' => $this->unit_id,
        ]);

        session()->flash('message', 'Atendimento atualizado com sucesso!');
        return redirect()->route('terapias-realizadas.index');
    }

    public function render()
    {
        $allowedUnitIds = auth()->user()->getAllowedUnitIds();

        // 1. Inicia as queries
        // SEGURANÇA: preserva o isolamento por unidade, removendo apenas o SoftDeletingScope.
        $patientsQuery = Patient::withoutGlobalScope(SoftDeletingScope::class)->orderBy('name');
        $professionalsQuery = Professional::orderBy('name');

        // 2. Aplica as regras de segurança (Multi-tenancy)
        if ($allowedUnitIds !== null) {
            $patientsQuery->whereIn('unit_id', $allowedUnitIds);
            $professionalsQuery->whereHas('units', function($q) use ($allowedUnitIds) {
                $q->whereIn('units.id', $allowedUnitIds);
            });
        }

        // 3. Filtra os profissionais com base na terapia selecionada
        if (!empty($this->therapy_id)) {
            $professionalsQuery->whereHas('therapies', function($q) {
                $q->where('therapies.id', $this->therapy_id);
            });
        } else {
            // Se não houver terapia, não carrega profissionais
            $professionalsQuery->where('id', '<', 0); 
        }

        // 4. Retorna a view com todas as variáveis obrigatórias
        return view('livewire.terapias-realizadas.edit', [
            'patients' => $patientsQuery->get(),
            'therapies' => Therapy::orderBy('name')->get(),
            'serviceTypes' => ServiceType::orderBy('name')->get(),
            'professionals' => $professionalsQuery->get(),
            'agreements' => Agreement::orderBy('name')->get(),
            'units' => Unit::whereIn('id', $this->unidadesPermitidasIds())->orderBy('name')->get(),
        ]);
    }
}