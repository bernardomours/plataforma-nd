<?php

namespace App\Livewire\AgendaProfissionais;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Professional;
use App\Models\Schedule;
use Carbon\Carbon;

#[Layout('layouts.app')]
class Index extends Component
{
    public $professional_id = '';

    public function getAgendaProperty()
    {
        $vazio = [1 => [], 2 => [], 3 => [], 4 => [], 5 => []];
        
        $agenda = [
            'Manhã' => $vazio,
            'Tarde' => $vazio,
        ];

        if (!$this->professional_id) {
            return $agenda;
        }

        $horarios = Schedule::with(['patient', 'therapy', 'serviceType'])
            ->where('professional_id', $this->professional_id)
            ->orderBy('start_time')
            ->get();

        foreach ($horarios as $horario) {
            $horaInicio = Carbon::parse($horario->start_time)->format('H:i:s');
            $turno = $horaInicio < '12:00:00' ? 'Manhã' : 'Tarde';
            
            $diaBanco = (string) $horario->day_of_week;
            
            $diaNumerico = match(strtolower(trim($diaBanco))) {
                'segunda' => 1,
                'terca', 'terça' => 2,
                'quarta' => 3,
                'quinta' => 4,
                'sexta' => 5,
                default => 1, 
            };

            if (isset($agenda[$turno][$diaNumerico])) {
                $agenda[$turno][$diaNumerico][] = $horario;
            }
        }

        return $agenda;
    }

    public function render()
    {
        $allowedUnits = auth()->user()->getAllowedUnitIds();

        $profissionais = Professional::orderBy('name')
            ->when($allowedUnits !== null, function ($q) use ($allowedUnits) {
                $q->whereHas('units', function ($query) use ($allowedUnits) {
                    $query->whereIn('units.id', $allowedUnits);
                });
            })->get();

        return view('livewire.agenda-profissionais.index', [
            'profissionais' => $profissionais,
            'agenda' => $this->agenda,
            'diasDaSemana' => [1 => 'SEGUNDA', 2 => 'TERÇA', 3 => 'QUARTA', 4 => 'QUINTA', 5 => 'SEXTA']
        ]);
    }
}