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
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Log;

class Edit extends Component
{
    public Patient $patient;

    public $showModal = false;

    public $name, $birth_date, $cpf, $agreement_number, $guardian_name, $guardian_phone;
    public $unit_id, $agreement_id;
    
    public $patientServices = [];

    public function mount(Patient $patient)
    {
        $this->patient = $patient;
        $this->preencherFormulario();
    }

    private function preencherFormulario()
    {
        $this->name = $this->patient->name;
        $this->birth_date = $this->patient->birth_date ? $this->patient->birth_date->format('Y-m-d') : null;
        $this->cpf = $this->patient->cpf;
        $this->agreement_number = $this->patient->agreement_number;
        $this->guardian_name = $this->patient->guardian_name;
        $this->guardian_phone = $this->patient->guardian_phone;
        $this->unit_id = $this->patient->unit_id;
        $this->agreement_id = $this->patient->agreement_id;

        $this->patientServices = $this->patient->patientServices->toArray();

        if (empty($this->patientServices)) {
            $this->addService();
        }
    }

    #[On('abrir-modal-editar-paciente')]
    public function abrirModal()
    {
        if (! auth()->user()->hasAnyRole(['admin', 'manager', 'administrative'])) {
            abort(403, 'Você não tem permissão para editar dados de pacientes.');
        }

        $this->preencherFormulario();
        $this->showModal = true;
    }

    public function fecharModal()
    {
        $this->showModal = false;
        $this->resetValidation();
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
            'unit_id' => ['required', 'integer', Rule::in($this->unidadesAtribuiveisIds())],
            'agreement_id' => 'required|exists:agreements,id',
            
            'patientServices.*.service_type_id' => 'required|exists:service_types,id',
            'patientServices.*.coordinator_id' => 'nullable|exists:professionals,id',
            'patientServices.*.supervisor_id' => 'nullable|exists:professionals,id',
        ];
    }

    private function unidadesAtribuiveisIds(): array
    {
        $allowedUnitIds = auth()->user()->getAllowedUnitIds();

        $permitidas = $allowedUnitIds === null
            ? Unit::pluck('id')->all()
            : $allowedUnitIds;

        return array_values(array_unique(array_merge(
            array_map('intval', $permitidas),
            [(int) $this->patient->unit_id]
        )));
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
        if (! auth()->user()->hasAnyRole(['admin', 'manager', 'administrative'])) {
            abort(403, 'Você não tem permissão para editar dados de pacientes.');
        }

        $this->validate();

        try {
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

            $this->showModal = false;
            
            $this->dispatch('paciente-atualizado'); 
            
            $this->dispatch('notify', type: 'success', message: 'Cadastro atualizado com sucesso!');

        } catch (\Exception $e) {
            Log::error('Falha ao atualizar paciente', [
                'patient_id' => $this->patient->id,
                'user_id'    => auth()->id(),
                'exception'  => $e->getMessage(),
            ]);

            $this->dispatch('notify', type: 'error', message: 'Erro ao salvar: verifique os dados ou tente novamente.');
        }
    }

    public function render()
    {
        $coordinators = collect();
        $supervisors = collect();

        if ($this->unit_id && in_array((int) $this->unit_id, $this->unidadesAtribuiveisIds(), true)) {
            $coordinators = Professional::where('role', 'coordinator')
                ->whereHas('units', fn ($q) => $q->where('units.id', $this->unit_id))
                ->get();

            $supervisors = Professional::where('role', 'supervisor')
                ->whereHas('units', fn ($q) => $q->where('units.id', $this->unit_id))
                ->get();
        }

        return view('livewire.pacientes.edit', [
            'units' => Unit::whereIn('id', $this->unidadesAtribuiveisIds())->get(),
            'agreements' => Agreement::all(),
            'serviceTypes' => ServiceType::all(),
            'coordinators' => $coordinators,
            'supervisors' => $supervisors,
        ]);
    }
}