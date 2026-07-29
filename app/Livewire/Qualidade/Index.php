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

        $checklists = QualityChecklist::where('quality_process_id', $checklist->quality_process_id)
                                      ->orderBy('id')
                                      ->get();

        $currentIndex = $checklists->search(fn($c) => $c->id == $checklist->id);
        $isNowCompleted = !$checklist->is_completed;

        if ($isNowCompleted) {
            if ($currentIndex > 0 && !$checklists[$currentIndex - 1]->is_completed) return;
        } else {
            if ($currentIndex < ($checklists->count() - 1) && $checklists[$currentIndex + 1]->is_completed) return;
        }

        $checklist->update([
            'is_completed' => $isNowCompleted,
            'completed_by' => $isNowCompleted ? Auth::id() : null,
            'completed_at' => $isNowCompleted ? now() : null,
        ]);
        
        $process = QualityProcess::find($checklist->quality_process_id);
        
        if ($process) {
            $totalChecklists = $process->checklists()->count();
            $completedChecklists = $process->checklists()->where('is_completed', true)->count();
            
            $progress = $totalChecklists > 0 
                ? (int) round(($completedChecklists / $totalChecklists) * 100) 
                : 0;
            
            $status = 'em_andamento';
            
            if ($progress === 0) {
                $status = 'pendente';
            } elseif ($progress === 100) {
                $status = 'concluido';
            }

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