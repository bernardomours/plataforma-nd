<?php

namespace App\Livewire\Pacientes;

use App\Models\Patient;
use App\Models\RequestedService;
use App\Models\Therapy;
use App\Models\ServiceType;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

class CargaHoraria extends Component
{
    use WithPagination;

    public Patient $patient;

    public $filter_month_year = '';

    public $isModalOpen = false;
    public $editingRecordId = null;

    // Campos Fixos (Cabeçalho)
    public $month_year;
    public $requisition_number;

    // Repeater Dinâmico
    public $terapias = [];

    protected function rules()
    {
        return [
            'month_year' => 'required|date',
            'requisition_number' => 'required|string',
            'terapias' => 'required|array|min:1',
            'terapias.*.therapy_id' => 'required|exists:therapies,id',
            'terapias.*.service_type_id' => 'required|exists:service_types,id',
            'terapias.*.requested_hours' => 'required|numeric|min:0',
            'terapias.*.approved_hours' => 'nullable|numeric|min:0',
            'terapias.*.planned_hours' => 'nullable|numeric|min:0',
        ];
    }
    
    protected $messages = [
        'terapias.*.therapy_id.required' => 'A terapia é obrigatória.',
        'terapias.*.service_type_id.required' => 'O tipo é obrigatório.',
        'terapias.*.requested_hours.required' => 'A CH é obrigatória.',
    ];

    public function mount(Patient $patient)
    {
        $this->patient = $patient;
    }

    public function clearFilter()
    {
        $this->filter_month_year = '';
        $this->resetPage();
    }

    public function updatingFilterMonthYear()
    {
        $this->resetPage();
    }

    public function openModal()
    {
        $this->resetValidation();
        $this->resetForm();
        $this->adicionarTerapia(); // Já inicia com 1 linha vazia
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->editingRecordId = null;
        $this->month_year = '';
        $this->requisition_number = '';
        $this->terapias = [];
    }

    public function adicionarTerapia()
    {
        $this->terapias[] = [
            'therapy_id' => '',
            'service_type_id' => '',
            'requested_hours' => '',
            'approved_hours' => '',
            'planned_hours' => '',
        ];
    }

    public function removerTerapia($index)
    {
        unset($this->terapias[$index]);
        $this->terapias = array_values($this->terapias); 
    }

    public function editRecord($id)
    {
        $this->resetValidation(); 
        $this->resetForm();

        $record = RequestedService::findOrFail($id);

        $this->editingRecordId = $record->id;
        $this->month_year = Carbon::parse($record->month_year)->format('Y-m'); 
        $this->requisition_number = $record->requisition_number;
        
        // No modo de edição, carregamos apenas o registro específico no índice 0
        $this->terapias[0] = [
            'therapy_id' => $record->therapy_id,
            'service_type_id' => $record->service_type_id,
            'requested_hours' => $record->requested_hours,
            'approved_hours' => $record->approved_hours,
            'planned_hours' => $record->planned_hours,
        ];

        $this->isModalOpen = true;
    }

    private function processSave()
    {
        $this->validate();

        $formattedDate = $this->month_year . '-01';

        if ($this->editingRecordId) {
            $dadosEdicao = $this->terapias[0];
            
            RequestedService::find($this->editingRecordId)->update([
                'patient_id' => $this->patient->id,
                'month_year' => $formattedDate,
                'requisition_number' => $this->requisition_number,
                'therapy_id' => $dadosEdicao['therapy_id'],
                'service_type_id' => $dadosEdicao['service_type_id'],
                'requested_hours' => $dadosEdicao['requested_hours'],
                'approved_hours' => $dadosEdicao['approved_hours'] ?: null,
                'planned_hours' => $dadosEdicao['planned_hours'] ?: null,
            ]);
            
            session()->flash('message', 'Solicitação atualizada com sucesso!');
            
        } else {
            foreach ($this->terapias as $terapia) {
                RequestedService::create([
                    'patient_id' => $this->patient->id,
                    'month_year' => $formattedDate,
                    'requisition_number' => $this->requisition_number,
                    'therapy_id' => $terapia['therapy_id'],
                    'service_type_id' => $terapia['service_type_id'],
                    'requested_hours' => $terapia['requested_hours'],
                    'approved_hours' => $terapia['approved_hours'] ?: null,
                    'planned_hours' => $terapia['planned_hours'] ?: null,
                ]);
            }
            
            session()->flash('message', 'Solicitações criadas com sucesso!');
        }
    }

    public function saveRecord()
    {
        $this->processSave(); 
        $this->closeModal();  
    }

    
    public function deleteRecord($id)
    {
        RequestedService::findOrFail($id)->delete();
        session()->flash('message', 'Registro excluído com sucesso!');
    }

    public function render()
    {
        $query = RequestedService::with(['therapy', 'serviceType'])
            ->where('patient_id', $this->patient->id);

        if (!empty($this->filter_month_year)) {
            $date = Carbon::parse($this->filter_month_year);
            $query->whereYear('month_year', $date->year)
                  ->whereMonth('month_year', $date->month);
        }

        $query->orderBy('month_year', 'desc');

        $records = $query->get();

        $totals = [
            'requested' => $records->sum('requested_hours'),
            'approved' => $records->sum('approved_hours'),
            'planned' => $records->sum('planned_hours'),
        ];

        return view('livewire.pacientes.carga-horaria', [
            'records' => $records,
            'totals' => $totals,
            'therapies' => Therapy::all(),
            'serviceTypes' => ServiceType::all(),
        ]);
    }
}