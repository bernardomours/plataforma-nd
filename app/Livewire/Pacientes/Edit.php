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
class Edit extends Component
{
    public Patient $patient;

    public $name, $birth_date, $cpf, $agreement_number, $guardian_name, $guardian_phone;
    public $unit_id, $agreement_id;
    
    public $patientServices = [];

    public function mount(Patient $patient)
    {
        $this->patient = $patient;

        $this->name = $patient->name;
        $this->birth_date = $patient->birth_date ? $patient->birth_date->format('Y-m-d') : null;
        $this->cpf = $patient->cpf;
        $this->agreement_number = $patient->agreement_number;
        $this->guardian_name = $patient->guardian_name;
        $this->guardian_phone = $patient->guardian_phone;
        $this->unit_id = $patient->unit_id;
        $this->agreement_id = $patient->agreement_id;

        $this->patientServices = $patient->patientServices->toArray();

        if (empty($this->patientServices)) {
            $this->addService();
        }
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'birth_date' => 'required|date',
            'cpf' => ['required', 'string', 'max:14', Rule::unique('patients')->ignore($this->patient->id), new CpfValidate()],
            'agreement_number' => 'required|string',
            'guardian_name' => 'nullable|string|max:255',
            'guardian_phone' => 'nullable|string|max:20',
            'unit_id' => 'required|exists:units,id',
            'agreement_id' => 'required|exists:agreements,id',
            
            'patientServices.*.service_type_id' => 'required|exists:service_types,id',
            'patientServices.*.coordinator_id' => 'nullable|exists:professionals,id',
            'patientServices.*.supervisor_id' => 'nullable|exists:professionals,id',
        ];
    }

    public function addService()
    {
        $this->patientServices[] = ['service_type_id' => '', 'coordinator_id' => '', 'supervisor_id' => ''];
    }

    public function removeService($index)
    {
        unset($this->patientServices[$index]);
        $this->patientServices = array_values($this->patientServices);
    }

    public function update()
    {
        $this->validate();

        $this->patient->update([
            'name' => $this->name,
            'birth_date' => $this->birth_date,
            'cpf' => $this->cpf,
            'agreement_number' => $this->agreement_number,
            'guardian_name' => $this->guardian_name,
            'guardian_phone' => $this->guardian_phone,
            'unit_id' => $this->unit_id,
            'agreement_id' => $this->agreement_id,
        ]);

        $this->patient->patientServices()->delete();
        
        foreach ($this->patientServices as $service) {
            $service['coordinator_id'] = empty($service['coordinator_id']) ? null : $service['coordinator_id'];
            $service['supervisor_id'] = empty($service['supervisor_id']) ? null : $service['supervisor_id'];
            
            $this->patient->patientServices()->create($service);
        }

        session()->flash('message', "Paciente {$this->patient->name} atualizado com sucesso!");
        return redirect()->route('pacientes.index');
    }

    public function render()
    {
        $coordinators = collect();
        $supervisors = collect();

        if ($this->unit_id) {
            $coordinators = Professional::where('role', 'coordinator')
                ->whereHas('units', fn ($q) => $q->where('units.id', $this->unit_id))
                ->get();

            $supervisors = Professional::where('role', 'supervisor')
                ->whereHas('units', fn ($q) => $q->where('units.id', $this->unit_id))
                ->get();
        }

        return view('livewire.pacientes.edit', [
            'units' => Unit::all(),
            'agreements' => Agreement::all(),
            'serviceTypes' => ServiceType::all(),
            'coordinators' => $coordinators,
            'supervisors' => $supervisors,
        ]);
    }
}