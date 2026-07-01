<?php

namespace App\Livewire\Pacientes;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Patient;
use App\Models\Therapy;
use App\Models\ServiceType;
use App\Models\Agreement;
use setasign\Fpdi\Fpdi;
use App\Models\Unit;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $sortField = 'name';
    public $sortDirection = 'asc';

    #filtros
    public $unit_id = '';
    public $agreement_id = '';
    public $trashed_filter = '';

    #infos para frequencia
    public $isFrequenciaModalOpen = false;
    public $selectedPatientId = null;
    public $frequencia_therapy_id = '';
    public $frequencia_service_type_id = '';
    public $frequencia_escola_turno = '';
    public $frequencia_mes_execucao = '';

    #soft deletes e saída em massa
    public array $selected = [];         
    public bool $selectAll = false;    

    public bool $showSaidaModal = false;
    public string $motivoSaida = '';
    public string $observacaoSaida = '';

    protected array $motivosSaida = [
        'Alta' => 'Alta',
        'Suspensão' => 'Suspensão',
        'Solicitação do Responsável' => 'Solicitação do Responsável',
    ];

    public function getMotivosSaidaProperty()
    {
        return $this->motivosSaida;
    }

    public function updatedSelectAll($value)
    {
        $this->selected = $value
            ? $this->buildPacientesQuery()->pluck('id')->map(fn ($id) => (string) $id)->toArray()
            : [];
    }

    public function openSaidaModal()
    {
        if (empty($this->selected)) {
            return;
        }
        $this->reset(['motivoSaida', 'observacaoSaida']);
        $this->showSaidaModal = true;
    }

    public function closeSaidaModal()
    {
        $this->showSaidaModal = false;
    }

    public function confirmarSaida()
    {
        $this->validate([
            'motivoSaida' => 'required|in:' . implode(',', array_keys($this->motivosSaida)),
        ], [
            'motivoSaida.required' => 'Selecione o motivo principal.',
        ]);

        $motivoCompleto = $this->motivoSaida;
        if (!empty($this->observacaoSaida)) {
            $motivoCompleto .= ' - ' . $this->observacaoSaida;
        }

        $pacientes = Patient::whereIn('id', $this->selected)->get();

        foreach ($pacientes as $paciente) {
            $paciente->movementHistories()->create([
                'action'  => 'Saída',
                'reason'  => $motivoCompleto,
                'date'    => now(),
                'user_id' => auth()->id(),
            ]);
            $paciente->delete();
        }

        $this->selected = [];
        $this->selectAll = false;
        $this->showSaidaModal = false;

        session()->flash('message', count($pacientes) . ' paciente(s) movido(s) para o histórico de saída.');
    }


    public function restorePatient($patientId)
    {
        $paciente = Patient::withTrashed()->find($patientId);
        if ($paciente) {
            $paciente->restore();
            
            $paciente->movementHistories()->create([
                'action'  => 'Retorno',
                'reason'  => 'Restaurado da lixeira/histórico',
                'date'    => now(),
                'user_id' => auth()->id(),
            ]);

            session()->flash('message', 'Paciente restaurado com sucesso.');
        }
    }

    public function forceDeletePatient($patientId)
    {
        $paciente = Patient::withTrashed()->find($patientId);
        if ($paciente) {
            // Se houver relações (como movementHistories ou avaliações) que precisem de 
            // ser apagadas antes do paciente, coloque-as aqui. Opcionalmente:
            // $paciente->movementHistories()->delete(); 
            
            $paciente->forceDelete();
            session()->flash('message', 'Paciente excluído permanentemente do banco de dados.');
        }
    }

    public function clearFilters()
    {
        $this->reset(['search', 'unit_id', 'agreement_id', 'trashed_filter']);
        $this->resetPage();
    }

    public function updated($property)
    {
        if (in_array($property, ['search', 'unit_id', 'agreement_id', 'trashed_filter'])) {
            $this->resetPage();
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    private function buildPacientesQuery()
    {
        $query = Patient::with(['agreement', 'unit']);

        if (!empty($this->search)) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        // Blindagem de segurança por unidade (garantindo que não vê pacientes de outras clínicas)
        $allowedUnits = auth()->user()->getAllowedUnitIds();
        if ($allowedUnits !== null) {
            $query->whereIn('unit_id', $allowedUnits);
        }

        if (!empty($this->unit_id)) {
            $query->where('unit_id', $this->unit_id);
        }

        if (!empty($this->agreement_id)) {
            $query->where('agreement_id', $this->agreement_id);
        }

        // Lógica da Lixeira
        if ($this->trashed_filter === 'with_trashed') {
            $query->withTrashed();
        } elseif ($this->trashed_filter === 'only_trashed') {
            $query->onlyTrashed();
        }

        return $query->orderBy($this->sortField, $this->sortDirection);
    }


    public function render()
    {
        $pacientes = $this->buildPacientesQuery()->paginate(10);

        $allowedUnitIds = auth()->user()->getAllowedUnitIds();

        $conveniosStats = Agreement::withCount(['patients' => function ($q) use ($allowedUnitIds) {
            // Ao contar estatísticas, respeitar as unidades permitidas E remover os excluídos da conta
            if ($allowedUnitIds !== null) {
                $q->whereIn('unit_id', $allowedUnitIds);
            }
        }])
        ->having('patients_count', '>', 0)
        ->orderByDesc('patients_count')
        ->get();

        $unidadesDisponiveis = $allowedUnitIds === null 
            ? Unit::all() 
            : Unit::whereIn('id', $allowedUnitIds)->get();

        return view('livewire.pacientes.index', [
            'pacientes' => $pacientes,
            'conveniosStats' => $conveniosStats,
            'unidadesFiltro' => $unidadesDisponiveis,
            'conveniosFiltro' => Agreement::all(),
            'therapies' => Therapy::all(),
            'serviceTypes' => ServiceType::all()
        ]);
    }

    // ... (As funções do Modal de Frequência e Unimed permanecem iguais)
    public function openFrequenciaModal($patientId)
    {
        $this->resetValidation();
        $this->selectedPatientId = $patientId;
        $this->frequencia_therapy_id = '';
        $this->frequencia_service_type_id = '';
        $this->frequencia_escola_turno = '';
        $this->frequencia_mes_execucao = '';
        $this->isFrequenciaModalOpen = true;
    }

    public function closeFrequenciaModal()
    {
        $this->isFrequenciaModalOpen = false;
        $this->selectedPatientId = null;
    }

    public function gerarFolhaUnimed()
    {
        // (Lógica inalterada da Unimed)
        // ...
        $this->validate([
            'frequencia_therapy_id' => 'required|exists:therapies,id',
            'frequencia_service_type_id' => 'required|exists:service_types,id',
            'frequencia_escola_turno' => 'required|string',
            'frequencia_mes_execucao' => 'required',
        ]);

        $record = Patient::findOrFail($this->selectedPatientId);
        $terapia = Therapy::find($this->frequencia_therapy_id)->name ?? '';
        $local = ServiceType::find($this->frequencia_service_type_id)->name ?? '';

        $pdf = new Fpdi();
        
        $caminhoPdf = storage_path('app/templates/folha_unimed.pdf');
        $pdf->setSourceFile($caminhoPdf);
        
        $pagina = $pdf->importPage(1);
        $pdf->AddPage();
        $pdf->useTemplate($pagina, 0, 0, 210);
        
        $pdf->SetFont('Arial', '', 10);
        
        $pdf->SetXY(40, 46); 
        $pdf->Write(0, utf8_decode('NÚCLEO DESENVOLVE')); 
        
        $pdf->SetXY(165, 46);
        $terapiaELocal = $terapia . ' - ' . $local;
        $pdf->Write(0, utf8_decode($terapiaELocal));

        $pdf->SetXY(30, 54.5); 
        $pdf->Write(0, utf8_decode(strtoupper($record->name))); 
        
        $pdf->SetXY(165, 54.5); 
        $pdf->Write(0, utf8_decode($record->agreement_number ?? 'Não info.'));

        $pdf->SetXY(55, 62);
        $pdf->Write(0, utf8_decode($this->frequencia_escola_turno));
        
        $pdf->SetXY(170, 62);
        $pdf->Write(0, utf8_decode($record->guardian_phone ?? 'Não info.'));

        $pdf->SetXY(40, 69);
        $pdf->Write(0, utf8_decode(strtoupper($record->guardian_name ?? 'Não informado')));

        $mesFormatado = \Carbon\Carbon::parse($this->frequencia_mes_execucao)->format('m/Y');
        $pdf->SetXY(45, 76);
        $pdf->Write(0, utf8_decode($mesFormatado));
        
        $pdf->SetXY(135, 76);
        $pdf->Write(0, utf8_decode('NÚCLEO DESENVOLVE'));

        $nomeArquivo = 'Frequencia_' . str_replace(' ', '_', $record->name) . '.pdf';
        
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->Output('S');
        }, $nomeArquivo);
    }
}