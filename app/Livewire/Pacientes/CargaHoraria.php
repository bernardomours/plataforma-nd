<?php

namespace App\Livewire\Pacientes;

use App\Models\Patient;
use App\Models\RequestedService;
use App\Models\Therapy;
use App\Models\ServiceType;
use App\Services\PlannedSessionsFromSchedule;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

class CargaHoraria extends Component
{
    use WithPagination;

    public Patient $patient;

    public $filter_month_year = '';

    /** O paciente tem agenda cadastrada? Controla o aviso no modal. */
    public $temAgenda = false;

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
            // Total de sessoes do MES, derivado da agenda mas editavel.
            'terapias.*.planned_sessions' => 'nullable|integer|min:0',
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

        $this->temAgenda = app(PlannedSessionsFromSchedule::class)->pacienteTemAgenda($patient);
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

    /** Só coordenação (admin, manager, administrative) gerencia CH — mesmo grupo de /solicitacao-ch. */
    private function autorizarGestaoCH(): void
    {
        if (! auth()->user()->hasAnyRole(['admin', 'manager', 'administrative'])) {
            abort(403, 'Você não tem permissão para gerenciar cargas horárias.');
        }
    }

    public function openModal()
    {
        $this->autorizarGestaoCH();

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
            'planned_hours' => '',          // sessoes por SEMANA (contexto)
            'planned_sessions' => '',       // sessoes no MES (e o que vale no calculo)
            'planned_from_schedule' => false,
            'agenda_blocos' => [],
        ];
    }

    public function removerTerapia($index)
    {
        unset($this->terapias[$index]);
        $this->terapias = array_values($this->terapias); 
    }

    public function editRecord($id)
    {
        $this->autorizarGestaoCH();

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
            'planned_sessions' => $record->planned_sessions,
            'planned_from_schedule' => (bool) $record->planned_from_schedule,
            'agenda_blocos' => [],
        ];

        // CONGELAMENTO: na edicao carregamos o valor JA GRAVADO, nao o que a agenda diz
        // hoje. Se a grade mudou depois, o numero do mes fechado permanece o de entao;
        // os blocos servem apenas para a tela poder mostrar a divergencia.
        $this->carregarBlocosDaAgenda(0);

        $this->isModalOpen = true;
    }

    private function processSave()
    {
        $this->autorizarGestaoCH();

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
                'planned_sessions' => $dadosEdicao['planned_sessions'] !== '' ? (int) $dadosEdicao['planned_sessions'] : null,
                'planned_from_schedule' => (bool) ($dadosEdicao['planned_from_schedule'] ?? false),
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
                    // Congelado no salvamento (ver RegistroModal e a migration).
                    'planned_sessions' => $terapia['planned_sessions'] !== '' ? (int) $terapia['planned_sessions'] : null,
                    'planned_from_schedule' => (bool) ($terapia['planned_from_schedule'] ?? false),
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

    /**
     * Recalcula o planejado quando a linha ganha terapia + tipo, ou quando o mes muda.
     * Mesmo comportamento do modal de ChSolicitada, para as duas telas nao divergirem.
     */
    public function updated($property)
    {
        if (str_starts_with($property, 'terapias.')) {
            $partes = explode('.', $property);
            $indice = (int) ($partes[1] ?? -1);
            $campo  = $partes[2] ?? '';

            if (in_array($campo, ['therapy_id', 'service_type_id'], true)) {
                $this->preencherPelaAgenda($indice);
            }

            // Digitar o total a mao desmarca a origem "agenda".
            if ($campo === 'planned_sessions' && isset($this->terapias[$indice])) {
                $this->terapias[$indice]['planned_from_schedule'] = false;
            }

            return;
        }

        if ($property === 'month_year') {
            foreach (array_keys($this->terapias) as $i) {
                $this->preencherPelaAgenda($i);
            }
        }
    }

    /**
     * Aplica o valor que a agenda calcula HOJE, sob demanda.
     *
     * O único gatilho automático de preencherPelaAgenda() é trocar terapia/tipo — editar
     * um registro existente sem mexer nesses campos nunca dispara updated(), então uma CH
     * congelada como "sem agenda" continua assim para sempre mesmo depois de alguém montar
     * o horário do paciente. Foi o caso de campo que motivou este botão: o coordenador
     * cadastrou a agenda de ABA depois que a CH tinha sido salva sem ela, e não existia
     * como pedir para a tela conferir de novo sem editar terapia e tipo (o que reseta a
     * seleção) ou rodar `ch:recalcular-planejada` por SSH.
     */
    public function usarValorDaAgenda(int $indice): void
    {
        $this->preencherPelaAgenda($indice);
    }

    /**
     * Pre-preenche a linha com o que a agenda indica para a competencia escolhida.
     * Nao mexe em nada quando a combinacao terapia+tipo nao existe na agenda: o campo
     * segue manual e a tela avisa.
     */
    private function preencherPelaAgenda(int $indice): void
    {
        $derivado = $this->derivarDaAgenda($indice);

        if ($derivado === null) {
            if (isset($this->terapias[$indice])) {
                $this->terapias[$indice]['planned_from_schedule'] = false;
                $this->terapias[$indice]['agenda_blocos'] = [];
            }

            return;
        }

        $this->terapias[$indice]['planned_sessions'] = $derivado['mensal'];
        $this->terapias[$indice]['planned_hours'] = $derivado['semanal'];
        $this->terapias[$indice]['planned_from_schedule'] = true;
        $this->terapias[$indice]['agenda_blocos'] = $derivado['blocos'];
    }

    /** Carrega apenas os blocos, sem sobrescrever o valor gravado (usado na edicao). */
    private function carregarBlocosDaAgenda(int $indice): void
    {
        $derivado = $this->derivarDaAgenda($indice);

        if ($derivado !== null) {
            $this->terapias[$indice]['agenda_blocos'] = $derivado['blocos'];
            $this->terapias[$indice]['agenda_mensal'] = $derivado['mensal'];
        }
    }

    private function derivarDaAgenda(int $indice): ?array
    {
        $linha = $this->terapias[$indice] ?? null;

        if (! $linha || empty($this->month_year)) {
            return null;
        }

        if (empty($linha['therapy_id']) || empty($linha['service_type_id'])) {
            return null;
        }

        return app(PlannedSessionsFromSchedule::class)->paraCombinacao(
            $this->patient,
            $linha['therapy_id'],
            $linha['service_type_id'],
            Carbon::parse($this->month_year . '-01')
        );
    }

    
    public function deleteRecord($id)
    {
        $this->autorizarGestaoCH();

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
            // Soma o total MENSAL congelado; planned_hours guarda apenas o semanal.
            'planned' => $records->sum('planned_sessions'),
        ];

        return view('livewire.pacientes.carga-horaria', [
            'records' => $records,
            'totals' => $totals,
            'therapies' => Therapy::all(),
            'serviceTypes' => ServiceType::all(),
        ]);
    }
}