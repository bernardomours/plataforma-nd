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
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rule;

#[Layout('layouts.app')]
class Create extends Component
{
    public $patient_id = '';
    public $therapy_id = '';
    public $service_type_id = '';
    public $professional_id = '';
    
    public $data_rapida = 'hoje';
    public $appointment_date;
    public $check_in;
    public $check_out;
    public $session_number;

    /**
     * Convênio e unidade DO ATENDIMENTO.
     *
     * Por padrão herdam o cadastro do paciente, mas ficam gravados na própria consulta —
     * assim um atendimento avulso feito como particular, ou realizado em outra unidade,
     * é contabilizado onde de fato ocorreu, e uma futura transferência do paciente não
     * reescreve o histórico.
     */
    public $agreement_id = '';
    public $unit_id = '';

    /** Controle do modal de sobrescrita. */
    public $showFaturamentoModal = false;

    public function mount()
    {
        $this->appointment_date = now()->timezone('America/Fortaleza')->format('Y-m-d');
    }

    /**
     * SEGURANÇA: sobrescrever convênio/unidade altera dado de faturamento, então é
     * restrito aos mesmos papéis que já podem lançar atendimento e editar o cadastro do
     * paciente. Os demais continuam salvando com o padrão herdado do paciente.
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

        if (empty($this->patient_id)) {
            $this->addError('patient_id', 'Selecione o paciente antes de alterar convênio ou unidade.');
            return;
        }

        $this->showFaturamentoModal = true;
    }

    public function fecharFaturamentoModal()
    {
        $this->showFaturamentoModal = false;
    }

    /** Devolve convênio e unidade ao padrão do cadastro do paciente. */
    public function restaurarPadraoPaciente()
    {
        $this->aplicarPadraoDoPaciente();
        $this->calculateSessions();
        $this->showFaturamentoModal = false;
    }

    /**
     * Carrega convênio e unidade a partir do paciente selecionado.
     * withoutGlobalScope(SoftDeletingScope) mantém o isolamento por unidade ativo.
     */
    private function aplicarPadraoDoPaciente(): void
    {
        if (empty($this->patient_id)) {
            $this->agreement_id = '';
            $this->unit_id = '';
            return;
        }

        $patient = Patient::withoutGlobalScope(SoftDeletingScope::class)
            ->select('id', 'agreement_id', 'unit_id')
            ->find($this->patient_id);

        $this->agreement_id = $patient->agreement_id ?? '';
        $this->unit_id = $patient->unit_id ?? '';
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
            // Preenchidos automaticamente a partir do paciente; só mudam pelo modal.
            'agreement_id' => 'required|exists:agreements,id',
            'unit_id' => ['required', Rule::in($this->unidadesPermitidasIds())],
        ];
    }

    /**
     * SEGURANÇA (multi-tenant): a unidade gravada no atendimento tem de estar entre as
     * permitidas ao usuário. Sem isto, um payload adulterado lançaria produção numa
     * clínica que o usuário não administra.
     */
    private function unidadesPermitidasIds(): array
    {
        $allowed = auth()->user()->getAllowedUnitIds();

        return $allowed === null
            ? Unit::pluck('id')->all()
            : array_map('intval', $allowed);
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
        // Ao trocar o paciente, convênio e unidade voltam ao padrão dele. Qualquer
        // sobrescrita anterior é descartada de propósito — ela pertencia ao outro paciente.
        $this->aplicarPadraoDoPaciente();
        $this->calculateSessions();
    }
    public function updatedCheckIn() { $this->calculateSessions(); }
    public function updatedCheckOut() { $this->calculateSessions(); }

    // Trocar o convênio no modal muda a regra de duração, então as sessões são refeitas
    // na hora — o usuário vê o novo número antes de concluir.
    public function updatedAgreementId() { $this->calculateSessions(); }

    // --- Lógica de Negócio ---

    private function calculateSessions()
    {
        if (empty($this->check_in) || empty($this->check_out)) {
            $this->session_number = null;
            return;
        }

        $sessionDuration = 40;

        // A duração da sessão passa a ser derivada do convênio DO ATENDIMENTO, não mais do
        // cadastro do paciente. Era essa dependência que fazia um atendimento avulso como
        // particular ser calculado pela regra da Humana (40 min) quando deveria usar 60.
        if (!empty($this->agreement_id) && !empty($this->therapy_id)) {
            $agreement = Agreement::find($this->agreement_id);
            $therapy = Therapy::find($this->therapy_id);

            if ($agreement && $therapy) {
                $isHumana = $agreement->name === 'Humana';
                $isAba = $therapy->name === 'ABA';

                if ($isHumana) {
                    $sessionDuration = 40;
                } else if ($isAba) {
                    $sessionDuration = 60;
                } else {
                    $sessionDuration = 40;
                }
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

    private function performSave()
    {
        $this->validate();

        // SEGURANÇA: 'exists:patients,id' aceita QUALQUER paciente do banco, inclusive de
        // outra clínica. Confirma a unidade antes de gravar o atendimento.
        $patientUnitId = Patient::withoutGlobalScopes()
            ->whereKey($this->patient_id)
            ->value('unit_id');

        if (! auth()->user()->canAccessUnit($patientUnitId)) {
            abort(403, 'Paciente fora das unidades permitidas.');
        }

        return Appointment::create([
            'patient_id' => $this->patient_id,
            'therapy_id' => $this->therapy_id,
            'service_type_id' => $this->service_type_id,
            'professional_id' => $this->professional_id,
            'appointment_date' => $this->appointment_date,
            'check_in' => $this->check_in,
            'check_out' => $this->check_out,
            'session_number' => $this->session_number,
            // Congelados no atendimento (ver migration add_agreement_and_unit_to_appointments).
            'agreement_id' => $this->agreement_id,
            'unit_id' => $this->unit_id,
        ]);
    }

    public function save()
    {
        $this->performSave();
        session()->flash('message', 'Atendimento registrado com sucesso!');
        return redirect()->route('terapias-realizadas.index');
    }

    public function saveAndCreateAnother()
    {
        $this->performSave();
        session()->flash('message', 'Atendimento registrado com sucesso!');
        
        $this->reset([
            'patient_id', 'therapy_id', 'service_type_id',
            'professional_id', 'check_in', 'check_out', 'session_number',
            // Limpos junto: pertenciam ao paciente do lançamento anterior.
            'agreement_id', 'unit_id',
        ]);
        
        $this->resetValidation();
    }

    public function render()
    {
        $allowedUnitIds = auth()->user()->getAllowedUnitIds();

        // SEGURANÇA: preserva o isolamento por unidade, removendo apenas o SoftDeletingScope.
        $patientsQuery = Patient::withoutGlobalScope(SoftDeletingScope::class)->orderBy('name');

        $professionalsQuery = Professional::orderBy('name');

        if ($allowedUnitIds !== null) {
            $patientsQuery->whereIn('unit_id', $allowedUnitIds);
            $professionalsQuery->whereHas('units', function($q) use ($allowedUnitIds) {
                $q->whereIn('units.id', $allowedUnitIds);
            });
        }

        if (!empty($this->therapy_id)) {
            $professionalsQuery->whereHas('therapies', function($q) {
                $q->where('therapies.id', $this->therapy_id);
            });
        } else {
            $professionalsQuery->where('id', '<', 0); 
        }

        return view('livewire.terapias-realizadas.create', [
            'patients' => $patientsQuery->get(),
            'therapies' => Therapy::orderBy('name')->get(),
            'serviceTypes' => ServiceType::orderBy('name')->get(),
            'professionals' => $professionalsQuery->get(),
            // Alimentam o modal de convênio/unidade do atendimento.
            'agreements' => Agreement::orderBy('name')->get(),
            'units' => Unit::whereIn('id', $this->unidadesPermitidasIds())->orderBy('name')->get(),
        ]);
    }
}