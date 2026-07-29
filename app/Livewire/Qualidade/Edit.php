<?php

namespace App\Livewire\Qualidade;

use Livewire\Component;
use App\Models\QualityProcess;
use App\Models\QualityChecklist;
use App\Models\User;
use Livewire\Attributes\Layout;
use Carbon\Carbon;

#[Layout('layouts.app')]
class Edit extends Component
{
    public QualityProcess $process;

    public $sector;
    public $procedure_code;
    public $process_name;
    public $due_date;
    public $selectedUsers = [];
    
    public array $checklists = [];

    public function mount($id) 
    {
        if (!auth()->user()->isAdmin() && !auth()->user()->isManager()) {
            abort(403, 'Acesso negado.');
        }

        $this->process = QualityProcess::with('checklists', 'users')->findOrFail($id);
        
        $this->sector = $this->process->sector;
        $this->procedure_code = $this->process->procedure_code;
        $this->process_name = $this->process->process_name;
        $this->due_date = $this->process->due_date ? $this->process->due_date->format('Y-m-d') : null;
        $this->selectedUsers = $this->process->users->pluck('id')->toArray();

        $this->checklists = $this->process->checklists()->orderBy('id')->get()->map(function($check) {
            return ['id' => $check->id, 'description' => $check->description];
        })->toArray();
    }

    public function addChecklist()
    {
        $this->checklists[] = ['id' => null, 'description' => ''];
    }

    public function removeChecklist($index)
    {
        unset($this->checklists[$index]);
        $this->checklists = array_values($this->checklists);
    }

    public function update()
    {
        $this->validate([
            'sector' => 'required|string|max:255',
            'procedure_code' => 'required|string|max:255',
            'process_name' => 'required|string|max:255',
            'due_date' => 'nullable|date',
            'selectedUsers' => 'array',
            'checklists.*.description' => 'required|string|max:255',
        ], [
            'checklists.*.description.required' => 'A descrição da etapa não pode ficar vazia.'
        ]);

        $this->process->update([
            'sector' => $this->sector,
            'procedure_code' => $this->procedure_code,
            'process_name' => $this->process_name,
            'due_date' => $this->due_date,
        ]);
        
        $this->process->users()->sync($this->selectedUsers);

        $existingStepIds = [];
        
        $dataBase = $this->due_date ? Carbon::parse($this->due_date) : null;
        
        foreach ($this->checklists as $index => $stepData) {
            
            $dataDaEtapa = $dataBase ? $dataBase->copy()->addDays($index * 30) : null;
            $dataFormatada = $dataDaEtapa ? $dataDaEtapa->format('Y-m-d') : null;

            if (isset($stepData['id']) && $stepData['id']) {
                $check = QualityChecklist::find($stepData['id']);
                if ($check) {
                    $check->update([
                        'description' => $stepData['description'],
                        'due_date' => $dataFormatada
                    ]);
                    $existingStepIds[] = $check->id;
                }
            } else {
                $newCheck = $this->process->checklists()->create([
                    'description' => $stepData['description'],
                    'due_date' => $dataFormatada,
                    'is_completed' => false
                ]);
                $existingStepIds[] = $newCheck->id;
            }
        }

        $this->process->checklists()->whereNotIn('id', $existingStepIds)->delete();

        $totalChecklists = $this->process->checklists()->count();
        $completedChecklists = $this->process->checklists()->where('is_completed', true)->count();
        $progress = $totalChecklists > 0 ? (int) round(($completedChecklists / $totalChecklists) * 100) : 0;
        
        $status = 'em_andamento';
        if ($progress === 0) $status = 'pendente';
        elseif ($progress === 100) $status = 'concluido';
        
        $this->process->update(['progress' => $progress, 'status' => $status]);

        session()->flash('message', 'Processo e etapas atualizados com sucesso!');
        return redirect()->route('qualidade.index');
    }

    public function render()
    {
        // Filtro de perfis idêntico ao do Create
        $usuariosFiltrados = User::role(['admin', 'manager', 'administrative'])
                                 ->orderBy('name')
                                 ->get();

        return view('livewire.qualidade.edit', [
            'allUsers' => $usuariosFiltrados 
        ]);
    }
}