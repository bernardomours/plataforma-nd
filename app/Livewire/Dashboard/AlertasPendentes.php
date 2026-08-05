<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use App\Models\Visit;
use App\Models\Professional;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use Carbon\Carbon;

class AlertasPendentes extends Component
{
    public $modalAberto = false;
    public $visitaSelecionadaId = null;
    public $visitaSelecionadaInfo = [];

    public function abrirOpcoes($id)
    {
        $visita = Visit::with(['patient'])->find($id);
        
        if ($visita) {
            $this->visitaSelecionadaId = $visita->id;
            
            $this->visitaSelecionadaInfo = [
                'paciente' => $visita->patient->name ?? 'Paciente não encontrado',
                'tipo' => $visita->type instanceof \App\Enums\VisitType ? $visita->type->getLabel() : $visita->type,
                'data' => $visita->created_at->format('d/m/Y'),
            ];
            
            $this->modalAberto = true;
        }
    }

    public function fecharModal()
    {
        $this->modalAberto = false;
        $this->visitaSelecionadaId = null;
        $this->visitaSelecionadaInfo = [];
    }

    public function excluirVisita()
    {
        if ($this->visitaSelecionadaId) {
            Visit::find($this->visitaSelecionadaId)?->delete();
            $this->fecharModal();
        }
    }

    public function render()
    {
        $dataLimite = Carbon::now()->subDays(10);
        $user = auth()->user();

        $query = Visit::with(['patient', 'professional', 'therapy'])
            ->whereIn('type', [VisitType::Coordination, VisitType::Supervision]) 
            ->where('status', VisitStatus::Pending) 
            ->where('created_at', '<=', $dataLimite);

        $allowedUnits = $user->getAllowedUnitIds();
        
        if ($allowedUnits !== null) {
            $query->whereHas('patient', function ($q) use ($allowedUnits) {
                $q->whereIn('unit_id', $allowedUnits);
            });
        }

        if ($user->hasAnyRole(['coordinator', 'supervisor'])) {
            $profissional = Professional::where('user_id', $user->id)->first();
            
            if ($profissional) {
                $query->where('professional_id', $profissional->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $visitasAtrasadas = $query->orderBy('created_at', 'asc')->get();

        return view('livewire.dashboard.alertas-pendentes', [
            'visitasAtrasadas' => $visitasAtrasadas
        ]);
    }
}