<?php

namespace App\Livewire\Qualidade;

use Livewire\Component;
use App\Models\QualityProcess;
use App\Models\QualityChecklist;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class Index extends Component
{
    public function toggleChecklist($checklistId)
    {
        $checklist = QualityChecklist::find($checklistId);
        
        if (!$checklist) return;

        // Pega todos os checklists do processo para validar a sequência
        $checklists = QualityChecklist::where('quality_process_id', $checklist->quality_process_id)
                                      ->orderBy('id')
                                      ->get();

        $currentIndex = $checklists->search(fn($c) => $c->id == $checklist->id);
        $isNowCompleted = !$checklist->is_completed;

        // Validação de Sequência
        if ($isNowCompleted) {
            if ($currentIndex > 0 && !$checklists[$currentIndex - 1]->is_completed) return;
        } else {
            if ($currentIndex < ($checklists->count() - 1) && $checklists[$currentIndex + 1]->is_completed) return;
        }

        // 1. Atualiza a etapa atual
        $checklist->update([
            'is_completed' => $isNowCompleted,
            'completed_by' => $isNowCompleted ? Auth::id() : null,
            'completed_at' => $isNowCompleted ? now() : null,
        ]);
        
        // 2. RECÁLCULO ABSOLUTO DO PROCESSO PAI
        $process = QualityProcess::find($checklist->quality_process_id);
        
        if ($process) {
            // Conta exatamente quantos existem e quantos estão concluídos
            $totalChecklists = $process->checklists()->count();
            $completedChecklists = $process->checklists()->where('is_completed', true)->count();
            
            // Calcula a porcentagem (evita divisão por zero)
            $progress = $totalChecklists > 0 
                ? (int) round(($completedChecklists / $totalChecklists) * 100) 
                : 0;
            
            // Define o status inteligente baseado na matemática real
            $status = 'em_andamento';
            
            if ($progress === 0) {
                $status = 'pendente';
            } elseif ($progress === 100) {
                $status = 'concluido';
            }

            // Atualiza o banco com a dupla imbatível: status e progresso sempre sincronizados
            $process->update([
                'progress' => $progress,
                'status' => $status
            ]);
        }
    }

    public function render()
    {
        $user = auth()->user();

        if ($user->isAdmin() || $user->isManager()) {
            $processes = QualityProcess::with(['users', 'checklists'])
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $processes = QualityProcess::whereHas('users', function($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->with(['users', 'checklists'])
            ->orderBy('created_at', 'desc')
            ->get();
        }

        return view('livewire.qualidade.index', [
            'processes' => $processes
        ]);
    }
    
}