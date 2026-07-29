<?php

namespace App\Livewire\Qualidade;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\QualityProcess;
use App\Models\User;
use Carbon\Carbon;

#[Layout('layouts.app')]
class Create extends Component
{
    public $sector = '';
    public $process_name = '';
    public $procedure_code = '';
    public $due_date = '';
    
    
    public $selectedUsers = [];
    
    public $checklists = [];

    public function mount()
    {
        $this->checklists = [
            ['description' => 'Não Iniciado'],
            ['description' => 'Em elaboração'],
            ['description' => 'Em implementação'],
            ['description' => 'Treinado'],
            ['description' => 'Auditado'],
            ['description' => 'Concluído'],
        ];
    }

    public function addChecklist()
    {
        $this->checklists[] = ['description' => ''];
    }

    public function removeChecklist($index)
    {
        unset($this->checklists[$index]);
        $this->checklists = array_values($this->checklists);
    }

    public function save()
    {
        $this->validate([
            'sector' => 'required|string|max:255',
            'process_name' => 'required|string|max:255',
            'procedure_code' => 'required|string|max:255',
            'due_date' => 'nullable|date',
            'selectedUsers' => 'required|array|min:1',
            'checklists' => 'required|array|min:1',
            'checklists.*.description' => 'required|string|max:255',
        ]);

        $process = QualityProcess::create([
            'sector' => $this->sector,
            'process_name' => $this->process_name,
            'procedure_code' => $this->procedure_code,
            'due_date' => $this->due_date, 
            'created_by' => auth()->id(),
            'status' => 'pendente',
            'progress' => 0,
        ]);

        $process->users()->attach($this->selectedUsers);

        $dataBase = $this->due_date ? Carbon::parse($this->due_date) : null;

        foreach ($this->checklists as $index => $item) {
            $dataDaEtapa = $dataBase ? $dataBase->copy()->addDays($index * 30) : null;

            $process->checklists()->create([
                'description' => $item['description'],
                'due_date' => $dataDaEtapa ? $dataDaEtapa->format('Y-m-d') : null,
            ]);
        }

        session()->flash('message', 'Processo da Qualidade criado com sucesso!');
        return redirect()->route('qualidade.index');
    }

    public function render()
    {
        $usuariosFiltrados = User::role(['admin', 'manager', 'administrative'])
                                 ->orderBy('name')
                                 ->get();

        return view('livewire.qualidade.create', [
            'allUsers' => $usuariosFiltrados 
        ]);
    }
}