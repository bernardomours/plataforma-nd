<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use App\Models\Patient;
use App\Models\Professional;
use App\Models\User;
use Carbon\Carbon;

class Aniversariantes extends Component
{
    public function render()
    {
        $hoje = Carbon::today();
        $allowedUnits = auth()->user()->getAllowedUnitIds();

        $pacientesQuery = Patient::with('unit')
            ->whereMonth('birth_date', $hoje->month)
            ->whereDay('birth_date', $hoje->day);

        $profissionaisQuery = Professional::with('units')
            ->whereMonth('birth_date', $hoje->month)
            ->whereDay('birth_date', $hoje->day);

        $usersQuery = User::with('units')
            ->whereMonth('birth_date', $hoje->month)
            ->whereDay('birth_date', $hoje->day);

        if ($allowedUnits !== null) {
            if (empty($allowedUnits)) {
                $pacientesQuery->whereRaw('1 = 0');
                $profissionaisQuery->whereRaw('1 = 0');
                $usersQuery->whereRaw('1 = 0');
            } else {
                $pacientesQuery->whereIn('unit_id', $allowedUnits);
                
                $profissionaisQuery->whereHas('units', function ($q) use ($allowedUnits) {
                    $q->whereIn('unit_id', $allowedUnits);
                });
                
                $usersQuery->whereHas('units', function ($q) use ($allowedUnits) {
                    $q->whereIn('unit_id', $allowedUnits);
                });
            }
        }

        $equipe = $profissionaisQuery->get()->concat($usersQuery->get())
            ->unique(function ($pessoa) {
                return $pessoa->email ?: $pessoa->name;
            })->values();

        return view('livewire.dashboard.aniversariantes', [
            'pacientes' => $pacientesQuery->get(),
            'equipe' => $equipe,
        ]);
    }
}