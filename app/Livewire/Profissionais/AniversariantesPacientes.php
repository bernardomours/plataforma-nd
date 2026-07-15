<?php

namespace App\Livewire\Profissionais;

use Livewire\Component;
use App\Models\Schedule;
use App\Models\Patient;
use Carbon\Carbon;

class AniversariantesPacientes extends Component
{
    public function render()
    {
        $aniversariantes = collect();
        $profissional = auth()->user()->professional;

        if ($profissional) {
            $patientIds = Schedule::where('professional_id', $profissional->id)
                                ->pluck('patient_id')
                                ->unique();

            $aniversariantes = Patient::whereIn('id', $patientIds)
                ->whereMonth('birth_date', now()->month)
                ->orderByRaw('DAY(birth_date)')
                ->get();
        }

        return view('livewire.profissionais.aniversariantes-pacientes', [
            'aniversariantes' => $aniversariantes,
            'mesAtual' => now()->translatedFormat('F')
        ]);
    }
}