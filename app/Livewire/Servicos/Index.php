<?php

namespace App\Livewire\Servicos;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Unit;
use App\Models\Therapy;
use App\Models\Agreement;

#[Layout('layouts.app')]
class Index extends Component
{
    public function getCanEditProperty()
    {
        $role = auth()->user()->role; 
        return in_array($role, ['admin', 'manager']);
    }

    public function toggleTherapyUnit($therapyId, $unitId)
    {
        if (!$this->canEdit) {
            return;
        }

        $therapy = Therapy::findOrFail($therapyId);
        $therapy->units()->toggle($unitId);
    }

    public function toggleAgreementUnit($agreementId, $unitId)
    {
        if (!$this->canEdit) {
            return;
        }

        $agreement = Agreement::findOrFail($agreementId);
        $agreement->units()->toggle($unitId);
    }

    public function render()
    {
        return view('livewire.servicos.index', [
            'units' => Unit::all(),
            'therapies' => Therapy::with('units')->get(),
            'agreements' => Agreement::with('units')->get(),
        ]);
    }
}