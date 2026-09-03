<?php

namespace App\Livewire\Profissionais;

use App\Models\Falta;
use App\Models\Schedule;
use App\Services\StatusAgendaDoDia;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Component;

class MinhaAgenda extends Component
{
    public bool $isModalFaltaOpen = false;
    public ?int $scheduleSelecionadoId = null;
    public $motivo = '';
    public $observacao = '';

    /**
     * SEGURANÇA (IDOR): confere que o horário pertence mesmo ao profissional logado
     * antes de deixar abrir modal ou registrar falta — o ID chega direto da requisição
     * do Livewire, forjável como qualquer outro.
     */
    private function autorizarSchedule(int $scheduleId): Schedule
    {
        $profissional = auth()->user()->professional;
        $schedule = Schedule::with(['patient', 'therapy'])->findOrFail($scheduleId);

        if (! $profissional || (int) $schedule->professional_id !== (int) $profissional->id) {
            abort(403, 'Você só pode registrar falta na própria agenda.');
        }

        return $schedule;
    }

    public function abrirModalFalta(int $scheduleId): void
    {
        $schedule = $this->autorizarSchedule($scheduleId);

        if (app(StatusAgendaDoDia::class)->jaResolvido($schedule, Carbon::today())) {
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
        $schedule = $this->autorizarSchedule($this->scheduleSelecionadoId);

        $this->validate([
            'motivo' => ['required', Rule::in(array_keys(Falta::MOTIVO_OPTIONS))],
            'observacao' => 'nullable|string|max:1000',
        ]);

        $resolver = app(StatusAgendaDoDia::class);
        if ($resolver->jaResolvido($schedule, Carbon::today())) {
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
            'date' => Carbon::today()->format('Y-m-d'),
            'motivo' => $this->motivo,
            'observacao' => $this->observacao ?: null,
            'registered_by' => auth()->id(),
        ]);

        $this->fecharModalFalta();
        session()->flash('message', 'Falta registrada com sucesso.');
    }

    public function render()
    {
        $agendamentos = collect();
        $statusPorSchedule = collect();
        $profissional = auth()->user()->professional;

        if ($profissional) {
            // Descobre qual é o dia da semana atual (0 = Domingo, 1 = Segunda...)
            $diaSemanaNumero = now()->dayOfWeek;

            // Mapeia para os nomes usados no seu banco de dados
            $dias = [
                0 => 'domingo',
                1 => 'segunda',
                2 => 'terça',
                3 => 'quarta',
                4 => 'quinta',
                5 => 'sexta',
                6 => 'sábado'
            ];

            $hojeNome = $dias[$diaSemanaNumero];
            $hojeNomeSemAcento = str_replace('ç', 'c', $hojeNome); // Prevenção para "terca" ou "terça"

            // Busca os horários de hoje deste profissional específico. Bloqueio de
            // horário ("Notificar Indisponível") não tem paciente nem terapia — sem o
            // filtro, aparecia na lista como "Paciente Indefinido/Removido".
            //
            // withTrashed() no patient: paciente com saída registrada continua
            // mostrando o nome (com badge "Inativo" no blade) em vez do genérico
            // "Paciente Removido" — mesmo pedido do usuário em AgendaProfissionais\Index.
            $agendamentos = Schedule::with(['patient' => fn ($q) => $q->withTrashed(), 'therapy', 'serviceType'])
                ->where('professional_id', $profissional->id)
                ->where('is_blocked', false)
                ->where(function($query) use ($hojeNome, $hojeNomeSemAcento) {
                    $query->where('day_of_week', 'LIKE', $hojeNome)
                          ->orWhere('day_of_week', 'LIKE', $hojeNomeSemAcento);
                })
                ->orderBy('start_time')
                ->get();

            // Status do dia (pendente/realizado/falta) — pra ele ver se a recepção já
            // resolveu algo, e pra travar "Registrar Falta" no que já foi resolvido.
            $statusPorSchedule = app(StatusAgendaDoDia::class)
                ->resolver($agendamentos, Carbon::today())
                ->keyBy(fn ($item) => $item->schedule->id);
        }

        return view('livewire.profissionais.minha-agenda', [
            'agendamentos' => $agendamentos,
            'statusPorSchedule' => $statusPorSchedule,
            'diaSemana' => ucfirst($hojeNome ?? 'Hoje'),
            'motivoOptions' => Falta::MOTIVO_OPTIONS,
        ]);
    }
}
