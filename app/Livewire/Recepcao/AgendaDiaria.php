<?php

namespace App\Livewire\Recepcao;

use App\Models\Agreement;
use App\Models\Appointment;
use App\Models\Falta;
use App\Models\Patient;
use App\Models\Professional;
use App\Models\Schedule;
use App\Models\Therapy;
use App\Models\Unit;
use App\Services\StatusAgendaDoDia;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Agenda Diária da Recepção — embutida na página inicial (dashboard.blade.php) como
 * widget do papel 'recepcao', igual Minha Agenda já é pro profissional. Sem Layout
 * próprio de propósito: não é uma rota de página inteira, é um componente aninhado.
 *
 * Lista a grade fixa do dia (Schedule) cruzada com o que já aconteceu (Appointment/
 * Falta), agrupada em blocos de horário. "Sinalizar Realizada" cria o Appointment na
 * hora, sem precisar abrir Terapias Realizadas; "Registrar Falta" existe aqui como
 * reforço — a responsabilidade principal de sinalizar falta é do profissional, em
 * Minha Agenda.
 */
class AgendaDiaria extends Component
{
    public string $data;
    public $unit_id = '';

    // Filtros da grade do dia — nomes distintos de $professional_id (usado no form do
    // modal "Realizar") de propósito, pra não colidir com ele.
    public $filtro_patient_id = '';
    public $filtro_professional_id = '';
    public $filtro_therapy_id = '';

    /**
     * Convênios visíveis na grade — multi-seleção, Unimed começa DESMARCADO (pedido da
     * chefia): o convênio já tem relatório próprio com check-in/check-out e profissional
     * definidos, então esses pacientes só entram aqui como visualização (pra recepção
     * ter noção de quem está previsto no dia), nunca como ação — ver ehUnimed().
     */
    public array $filtro_agreement_ids = [];

    public bool $isModalRealizarOpen = false;
    public bool $isModalFaltaOpen = false;
    public ?int $scheduleSelecionadoId = null;

    public $professional_id = '';
    public $check_in = '';
    public $check_out = '';
    public $guide = '';

    public $motivo = '';
    public $observacao = '';

    public function mount()
    {
        $this->autorizarAcesso();

        $this->data = now()->format('Y-m-d');

        $allowedUnitIds = auth()->user()->getAllowedUnitIds();
        if ($allowedUnitIds !== null && count($allowedUnitIds) === 1) {
            $this->unit_id = $allowedUnitIds[0];
        }

        // Todos os convênios marcados, exceto Unimed — mantém o comportamento de antes
        // por padrão, só passa a mostrar Unimed se a recepção marcar o checkbox.
        $this->filtro_agreement_ids = Agreement::where('name', 'not like', '%unimed%')
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    private function autorizarAcesso(): void
    {
        if (! auth()->user()->hasAnyRole(['admin', 'manager', 'recepcao'])) {
            abort(403, 'Você não tem permissão para acessar a agenda diária da recepção.');
        }
    }

    public function irParaOntem(): void
    {
        $this->data = Carbon::parse($this->data)->subDay()->format('Y-m-d');
    }

    public function irParaAmanha(): void
    {
        $this->data = Carbon::parse($this->data)->addDay()->format('Y-m-d');
    }

    public function irParaHoje(): void
    {
        $this->data = now()->format('Y-m-d');
    }

    public function limparFiltros(): void
    {
        $this->reset(['filtro_patient_id', 'filtro_professional_id', 'filtro_therapy_id']);
    }

    /**
     * SEGURANÇA (IDOR + multi-unidade): o ID do horário chega direto da requisição do
     * Livewire — confere que a unidade do paciente vinculado está entre as permitidas
     * ao usuário antes de deixar abrir modal ou salvar qualquer coisa sobre ele.
     */
    private function autorizarSchedule(int $scheduleId): Schedule
    {
        $schedule = Schedule::with(['patient.agreement', 'professional', 'therapy', 'serviceType'])->findOrFail($scheduleId);

        if (! auth()->user()->canAccessUnit($schedule->patient?->unit_id)) {
            abort(403, 'Você não tem permissão para gerenciar a agenda desta unidade.');
        }

        // Unimed é só visualização nesta tela mesmo que o botão apareça de alguma forma
        // forjada — o botão de ação já não existe pra esses cartões no blade, isto é a
        // segunda trava, no servidor.
        if ($this->ehUnimed($schedule)) {
            abort(403, 'Atendimentos Unimed são geridos pelo relatório do convênio — não é possível registrar por aqui.');
        }

        return $schedule;
    }

    private function ehUnimed(Schedule $schedule): bool
    {
        return str_contains(mb_strtolower($schedule->patient?->agreement?->name ?? ''), 'unimed');
    }

    public function abrirModalRealizar(int $scheduleId): void
    {
        $this->autorizarAcesso();
        $schedule = $this->autorizarSchedule($scheduleId);

        if (app(StatusAgendaDoDia::class)->jaResolvido($schedule, Carbon::parse($this->data))) {
            session()->flash('error', 'Este horário já foi resolvido — atualize a página.');
            return;
        }

        $this->scheduleSelecionadoId = $schedule->id;
        $this->professional_id = $schedule->professional_id;
        $this->check_in = substr((string) $schedule->start_time, 0, 5);
        $this->check_out = substr((string) $schedule->end_time, 0, 5);
        $this->guide = '';
        $this->resetValidation();
        $this->isModalRealizarOpen = true;
    }

    public function fecharModalRealizar(): void
    {
        $this->isModalRealizarOpen = false;
        $this->reset(['professional_id', 'check_in', 'check_out', 'guide', 'scheduleSelecionadoId']);
    }

    public function salvarRealizado(): void
    {
        $this->autorizarAcesso();
        $schedule = $this->autorizarSchedule($this->scheduleSelecionadoId);

        $this->validate([
            'professional_id' => 'required|exists:professionals,id',
            'check_in' => 'required',
            'check_out' => 'required|after:check_in',
            'guide' => 'nullable|string|max:255',
        ]);

        $dataCarbon = Carbon::parse($this->data);
        $resolver = app(StatusAgendaDoDia::class);

        if ($resolver->jaResolvido($schedule, $dataCarbon)) {
            session()->flash('error', 'Este horário já foi resolvido por outra pessoa nesse meio-tempo.');
            $this->fecharModalRealizar();
            return;
        }

        $patient = $schedule->patient;

        Appointment::create([
            'schedule_id' => $schedule->id,
            'patient_id' => $schedule->patient_id,
            'professional_id' => $this->professional_id,
            'therapy_id' => $schedule->therapy_id,
            'service_type_id' => $schedule->service_type_id,
            'appointment_date' => $dataCarbon->format('Y-m-d'),
            'check_in' => $this->check_in,
            'check_out' => $this->check_out,
            'session_number' => $this->calcularSessoes($patient, $schedule->therapy, $this->check_in, $this->check_out),
            'agreement_id' => $patient?->agreement_id,
            'unit_id' => $patient?->unit_id,
            'guide' => $this->guide ?: null,
        ]);

        $this->fecharModalRealizar();
        session()->flash('message', 'Atendimento registrado com sucesso!');
    }

    /**
     * Mesma regra de duração usada em PlannedSessionsFromSchedule::duracaoDaSessao() e
     * replicada em TerapiasRealizadas\Create|Edit::calculateSessions() (ver CLAUDE.md,
     * "Duração da sessão"): Humana sempre 40min; ABA fora da Humana, 60min; demais, 40min.
     */
    private function calcularSessoes(?Patient $patient, ?Therapy $therapy, string $checkIn, string $checkOut): int
    {
        $inicio = \DateTime::createFromFormat('H:i', $checkIn);
        $fim = \DateTime::createFromFormat('H:i', $checkOut);

        if (! $inicio || ! $fim || $fim <= $inicio) {
            return 0;
        }

        $interval = $fim->diff($inicio);
        $minutos = ($interval->h * 60) + $interval->i;

        $isHumana = $patient?->agreement?->name === 'Humana';
        $isAba = $therapy?->name === 'ABA';
        $duracaoIdeal = $isHumana ? 40 : ($isAba ? 60 : 40);

        return (int) max(1, round($minutos / $duracaoIdeal));
    }

    public function abrirModalFalta(int $scheduleId): void
    {
        $this->autorizarAcesso();
        $schedule = $this->autorizarSchedule($scheduleId);

        if (app(StatusAgendaDoDia::class)->jaResolvido($schedule, Carbon::parse($this->data))) {
            session()->flash('error', 'Este horário já foi resolvido — atualize a página.');
            return;
        }

        $this->scheduleSelecionadoId = $schedule->id;
        $this->motivo = '';
        $this->observacao = '';
        $this->resetValidation();
        $this->isModalFaltaOpen = true;
    }

    public function fecharModalFalta(): void
    {
        $this->isModalFaltaOpen = false;
        $this->reset(['motivo', 'observacao', 'scheduleSelecionadoId']);
    }

    public function salvarFalta(): void
    {
        $this->autorizarAcesso();
        $schedule = $this->autorizarSchedule($this->scheduleSelecionadoId);

        $this->validate([
            'motivo' => ['required', Rule::in(array_keys(Falta::MOTIVO_OPTIONS))],
            'observacao' => 'nullable|string|max:1000',
        ]);

        $dataCarbon = Carbon::parse($this->data);
        $resolver = app(StatusAgendaDoDia::class);

        if ($resolver->jaResolvido($schedule, $dataCarbon)) {
            session()->flash('error', 'Este horário já foi resolvido por outra pessoa nesse meio-tempo.');
            $this->fecharModalFalta();
            return;
        }

        Falta::create([
            'schedule_id' => $schedule->id,
            'patient_id' => $schedule->patient_id,
            'professional_id' => $schedule->professional_id,
            'therapy_id' => $schedule->therapy_id,
            'service_type_id' => $schedule->service_type_id,
            'date' => $dataCarbon->format('Y-m-d'),
            'motivo' => $this->motivo,
            'observacao' => $this->observacao ?: null,
            'registered_by' => auth()->id(),
        ]);

        $this->fecharModalFalta();
        session()->flash('message', 'Falta registrada com sucesso.');
    }

    public function render()
    {
        $dataCarbon = Carbon::parse($this->data);
        $allowedUnitIds = auth()->user()->getAllowedUnitIds();

        [$diaNome, $diaSemAcento] = Schedule::nomesDoDiaDaSemana($dataCarbon);

        $query = Schedule::with(['patient.unit', 'patient.agreement', 'professional', 'therapy', 'serviceType'])
            ->where('is_blocked', false)
            ->where(function ($q) use ($diaNome, $diaSemAcento) {
                $q->where('day_of_week', 'LIKE', $diaNome)->orWhere('day_of_week', 'LIKE', $diaSemAcento);
            });

        if (! empty($this->unit_id)) {
            $query->whereHas('patient', fn ($q) => $q->where('unit_id', $this->unit_id));
        } elseif ($allowedUnitIds !== null) {
            $query->whereHas('patient', fn ($q) => $q->whereIn('unit_id', $allowedUnitIds));
        }

        // Convênio é filtro de visibilidade, não de ação: paciente sem convênio
        // cadastrado (Particular informal) sempre aparece, independente do que está
        // marcado — só os convênios de fato cadastrados entram/saem pelo checkbox.
        $query->whereHas('patient', function ($q) {
            $q->whereIn('agreement_id', $this->filtro_agreement_ids)->orWhereNull('agreement_id');
        });

        $schedulesDoDia = $query->get()->sortBy('start_time')->values();

        // Opções dos 3 filtros: lista completa (paciente/profissional da(s) unidade(s)
        // permitida(s); terapia é sistema inteiro, não depende de unidade) — não mais
        // restrita a quem está na grade do dia. Selecionar algo sem ninguém agendado
        // hoje simplesmente mostra a agenda vazia, o que é mais previsível do que a
        // lista mudar de tamanho a cada dia.
        $pacientesQuery = Patient::query();
        $profissionaisQuery = Professional::query();
        if (! empty($this->unit_id)) {
            $pacientesQuery->where('unit_id', $this->unit_id);
            $profissionaisQuery->whereHas('units', fn ($q) => $q->where('units.id', $this->unit_id));
        } elseif ($allowedUnitIds !== null) {
            $pacientesQuery->whereIn('unit_id', $allowedUnitIds);
            $profissionaisQuery->whereHas('units', fn ($q) => $q->whereIn('units.id', $allowedUnitIds));
        }
        $pacientesFiltro = $pacientesQuery->orderBy('name')->get();
        $profissionaisFiltro = $profissionaisQuery->orderBy('name')->get();
        $terapiasFiltro = Therapy::orderBy('name')->get();

        $schedules = $schedulesDoDia
            ->when($this->filtro_patient_id, fn ($c) => $c->where('patient_id', $this->filtro_patient_id))
            ->when($this->filtro_professional_id, fn ($c) => $c->where('professional_id', $this->filtro_professional_id))
            ->when($this->filtro_therapy_id, fn ($c) => $c->where('therapy_id', $this->filtro_therapy_id))
            ->values();

        $resolvidos = app(StatusAgendaDoDia::class)->resolver($schedules, $dataCarbon)
            ->map(function ($item) {
                $item->isUnimed = $this->ehUnimed($item->schedule);

                return $item;
            });

        // Blocos de 2h — clínica com muitos pacientes concentrados à tarde fica mais
        // escaneável assim do que em 3 turnos largos (manhã/tarde/noite).
        $blocos = $resolvidos->groupBy(function ($item) {
            $hora = (int) substr((string) $item->schedule->start_time, 0, 2);
            $inicioBloco = $hora - ($hora % 2);

            return sprintf('%02d:00 – %02d:00', $inicioBloco, $inicioBloco + 2);
        })->sortKeys();

        $unidadesFiltro = $allowedUnitIds === null
            ? Unit::orderBy('name')->get()
            : Unit::whereIn('id', $allowedUnitIds)->orderBy('name')->get();

        // Lista de profissionais pro select de substituição no modal "Realizar" — filtra
        // pela terapia do horário selecionado, igual o resto do sistema já faz.
        $profissionaisDaTerapia = collect();
        if ($this->scheduleSelecionadoId) {
            $scheduleSelecionado = $resolvidos->firstWhere('schedule.id', $this->scheduleSelecionadoId)?->schedule;
            if ($scheduleSelecionado) {
                $profissionaisDaTerapia = Professional::whereHas('therapies', fn ($q) => $q->where('therapies.id', $scheduleSelecionado->therapy_id))
                    ->orderBy('name')
                    ->get();
            }
        }

        // Unimed não entra no placar: é só visualização, não é "pendência" de recepção —
        // quem controla o que já aconteceu é o relatório do convênio, não esta tela.
        $resolvidosAcionaveis = $resolvidos->where('isUnimed', false);

        return view('livewire.recepcao.agenda-diaria', [
            'blocos' => $blocos,
            'dataCarbon' => $dataCarbon,
            'unidadesFiltro' => $unidadesFiltro,
            'pacientesFiltro' => $pacientesFiltro,
            'profissionaisFiltro' => $profissionaisFiltro,
            'terapiasFiltro' => $terapiasFiltro,
            'agreementsFiltro' => Agreement::orderBy('name')->get(),
            'totalPendente' => $resolvidosAcionaveis->where('status', StatusAgendaDoDia::PENDENTE)->count(),
            'totalRealizado' => $resolvidosAcionaveis->where('status', StatusAgendaDoDia::REALIZADO)->count(),
            'totalFalta' => $resolvidosAcionaveis->where('status', StatusAgendaDoDia::FALTA)->count(),
            'totalUnimed' => $resolvidos->where('isUnimed', true)->count(),
            'motivoOptions' => Falta::MOTIVO_OPTIONS,
            'profissionaisDaTerapia' => $profissionaisDaTerapia,
        ]);
    }
}
