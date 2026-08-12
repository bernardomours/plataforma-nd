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
use Livewire\Attributes\On; // <-- Importante adicionar isso

class Edit extends Component
{
    public Patient $patient;

    // Nova variável para controlar o Modal
    public $showModal = false;

    public $name, $birth_date, $cpf, $agreement_number, $guardian_name, $guardian_phone;
    public $unit_id, $agreement_id;
    
    public $patientServices = [];

    public function mount(Patient $patient)
    {
        $this->patient = $patient;
        $this->preencherFormulario();
    }

    // Isolar o preenchimento facilita se você quiser resetar o modal ao fechar
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

    // Este evento será chamado pelo botão no cabeçalho
    #[On('abrir-modal-editar-paciente')]
    public function abrirModal()
    {
        $this->preencherFormulario(); // Garante que os dados estão frescos
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
            // SEGURANÇA (item 7): 'exists:units,id' aceitava qualquer unidade do sistema —
            // era possível transferir um paciente para uma clínica que o usuário não
            // administra (e perder o acesso a ele, ou movê-lo indevidamente).
            // Restringe às unidades permitidas + a unidade atual do paciente (para não
            // quebrar o save de quem só edita outros campos).
            'unit_id' => ['required', 'integer', Rule::in($this->unidadesAtribuiveisIds())],
            'agreement_id' => 'required|exists:agreements,id',
            
            'patientServices.*.service_type_id' => 'required|exists:service_types,id',
            'patientServices.*.coordinator_id' => 'nullable|exists:professionals,id',
            'patientServices.*.supervisor_id' => 'nullable|exists:professionals,id',
        ];
    }

    /**
     * SEGURANÇA (multi-tenant): unidades que o usuário logado pode gravar neste paciente.
     * Inclui a unidade atual do registro para que a edição de outros campos não falhe
     * caso o paciente esteja numa unidade fora do escopo do usuário.
     */
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
        $this->validate();

        try {
            // Tentativa de atualizar o paciente
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

            // 1. Fecha o Modal apenas se deu tudo certo
            $this->showModal = false;
            
            // 2. Avisa a tela principal (Show.php) que o paciente mudou
            $this->dispatch('paciente-atualizado'); 
            
            // 3. Dispara a notificação de SUCESSO para o navegador
            $this->dispatch('notify', type: 'success', message: 'Cadastro atualizado com sucesso!');

        } catch (\Exception $e) {
            // OBSERVABILIDADE (item 12): o catch silencioso mascarava falhas de integridade
            // (ex.: FK de patient_services). Loga o contexto sem expor nada ao usuário —
            // a mensagem genérica na tela permanece exatamente a mesma.
            \Log::error('Falha ao atualizar paciente', [
                'patient_id' => $this->patient->id,
                'user_id'    => auth()->id(),
                'exception'  => $e->getMessage(),
            ]);

            // Se algo der errado no banco, NÃO fecha o modal e avisa o erro
            $this->dispatch('notify', type: 'error', message: 'Erro ao salvar: verifique os dados ou tente novamente.');
        }
    }

    public function render()
    {
        $coordinators = collect();
        $supervisors = collect();

        // SEGURANÇA: $this->unit_id vem do payload e é usado para listar profissionais.
        // Só consulta se a unidade estiver entre as permitidas, senão um valor adulterado
        // devolveria a lista de coordenadores/supervisores de outra clínica.
        if ($this->unit_id && in_array((int) $this->unit_id, $this->unidadesAtribuiveisIds(), true)) {
            $coordinators = Professional::where('role', 'coordinator')
                ->whereHas('units', fn ($q) => $q->where('units.id', $this->unit_id))
                ->get();

            $supervisors = Professional::where('role', 'supervisor')
                ->whereHas('units', fn ($q) => $q->where('units.id', $this->unit_id))
                ->get();
        }

        return view('livewire.pacientes.edit', [
            // SEGURANÇA: o select só oferece unidades permitidas (+ a atual do paciente),
            // espelhando exatamente a regra validada no backend.
            'units' => Unit::whereIn('id', $this->unidadesAtribuiveisIds())->get(),
            'agreements' => Agreement::all(),
            'serviceTypes' => ServiceType::all(),
            'coordinators' => $coordinators,
            'supervisors' => $supervisors,
        ]);
    }
}