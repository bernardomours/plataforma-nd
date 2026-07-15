<?php

namespace App\Livewire\Profissionais;

use Livewire\Component;
use App\Models\Schedule;
use Carbon\Carbon;

class MinhaAgenda extends Component
{
    public function render()
    {
        $agendamentos = collect();
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

            // Busca os horários de hoje deste profissional específico
            $agendamentos = Schedule::with(['patient', 'therapy', 'serviceType'])
                ->where('professional_id', $profissional->id)
                ->where(function($query) use ($hojeNome, $hojeNomeSemAcento) {
                    $query->where('day_of_week', 'LIKE', $hojeNome)
                          ->orWhere('day_of_week', 'LIKE', $hojeNomeSemAcento);
                })
                ->orderBy('start_time')
                ->get();
        }

        return view('livewire.profissionais.minha-agenda', [
            'agendamentos' => $agendamentos,
            'diaSemana' => ucfirst($hojeNome ?? 'Hoje')
        ]);
    }
}