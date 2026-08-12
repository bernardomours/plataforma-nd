<?php

namespace App\Livewire\Pacientes;

use App\Models\Patient;
use App\Models\Unit;
use App\Models\Agreement;
use App\Models\Professional;
use App\Models\ServiceType;
use App\Rules\CpfValidate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class Create extends Component
{
    public $name, $birth_date, $cpf, $agreement_number, $guardian_name, $guardian_phone;
    
    public $unit_id, $agreement_id;
    
    public $patientServices = [];

    public function mount()
    {
        $this->addService();
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'birth_date' => 'required|date',
            'cpf' => ['required', 'string', 'max:14', 'unique:patients,cpf', new CpfValidate()],
            'agreement_number' => 'required|string',
            'guardian_name' => 'nullable|string|max:255',
            'guardian_phone' => 'nullable|string|max:20',
            // SEGURANÇA (mesmo caso do Edit, item 7): impede cadastrar paciente numa
            // unidade que o usuário não administra.
            'unit_id' => ['required', 'integer', Rule::in($this->unidadesPermitidasIds())],
            'agreement_id' => 'required|exists:agreements,id',
            
            'patientServices.*.service_type_id' => 'required|exists:service_types,id',
            'patientServices.*.coordinator_id' => 'nullable|exists:professionals,id',
            'patientServices.*.supervisor_id' => 'nullable|exists:professionals,id',
        ];
    }

    public function messages()
    {
        return [
            'cpf.unique' => 'Esse CPF já está cadastrado.',
            'patientServices.*.service_type_id.required' => 'O tipo de serviço é obrigatório.'
        ];
    }

    /**
     * SEGURANÇA (multi-tenant): unidades em que o usuário logado pode cadastrar pacientes.
     * null em getAllowedUnitIds() = admin/manager = todas.
     */
    private function unidadesPermitidasIds(): array
    {
        $allowedUnitIds = auth()->user()->getAllowedUnitIds();

        return $allowedUnitIds === null
            ? Unit::pluck('id')->all()
            : array_map('intval', $allowedUnitIds);
    }

    public function addService()
    {
        $this->patientServices[] = [
            'service_type_id' => '', 
            'coordinator_id' => '', 
            'supervisor_id' => ''
        ];
    }

    public function removeService($index)
    {
        unset($this->patientServices[$index]);
        $this->patientServices = array_values($this->patientServices);
    }

    private function performSave()
    {
        $this->validate();

        $patient = Patient::create([
            'name' => $this->name,
            'birth_date' => $this->birth_date,
            'cpf' => $this->cpf,
            'agreement_number' => $this->agreement_number,
            'guardian_name' => $this->guardian_name,
            'guardian_phone' => $this->guardian_phone,
            'unit_id' => $this->unit_id,
            'agreement_id' => $this->agreement_id,
            'is_active' => true,
        ]);

        foreach ($this->patientServices as $service) {
            $service['coordinator_id'] = empty($service['coordinator_id']) ? null : $service['coordinator_id'];
            $service['supervisor_id'] = empty($service['supervisor_id']) ? null : $service['supervisor_id'];
            
            $patient->patientServices()->create($service);
        }

        return $patient;
    }

    public function save()
    {
        $patient = $this->performSave();

        session()->flash('message', "Paciente {$patient->name} cadastrado com sucesso.");
        
        return redirect()->route('pacientes.index');
    }

    public function saveAndCreateAnother()
    {
        $patient = $this->performSave();

        session()->flash('message', "Paciente {$patient->name} cadastrado com sucesso.");

        $this->reset([
            'name', 'birth_date', 'cpf', 'agreement_number', 
            'guardian_name', 'guardian_phone', 'unit_id', 'agreement_id', 
            'patientServices'
        ]);

        $this->addService();
    }

    public function render()
    {
        $coordinators = collect();
        $supervisors = collect();

        // SEGURANÇA: só lista profissionais se a unidade escolhida for permitida.
        if ($this->unit_id && in_array((int) $this->unit_id, $this->unidadesPermitidasIds(), true)) {
            $coordinators = Professional::where('role', 'coordinator')
                ->whereHas('units', fn ($q) => $q->where('units.id', $this->unit_id))
                ->get();

            $supervisors = Professional::where('role', 'supervisor')
                ->whereHas('units', fn ($q) => $q->where('units.id', $this->unit_id))
                ->get();
        }

        return view('livewire.pacientes.create', [
            // SEGURANÇA: select restrito às unidades permitidas (espelha a validação).
            'units' => Unit::whereIn('id', $this->unidadesPermitidasIds())->get(),
            'agreements' => Agreement::all(),
            'serviceTypes' => ServiceType::all(),
            'coordinators' => $coordinators,
            'supervisors' => $supervisors,
        ]);
    }
}