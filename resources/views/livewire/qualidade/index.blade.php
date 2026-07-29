<div class="max-w-7xl mx-auto py-8 sm:px-6 lg:px-8">
    
    @hasanyrole('admin|manager')
    <div class="mb-8 flex justify-between items-end">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Quadro de Qualidade</h2>
            <p class="text-sm text-gray-500 mt-1">Acompanhamento de Processos e Procedimentos Operacionais (POPs)</p>
        </div>
        @if(auth()->user()->isAdmin() || auth()->user()->isManager())
            <a href="{{ route('qualidade.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg shadow-sm transition inline-block">
                + Novo Processo
            </a>
        @endif
    </div>
    @endhasanyrole

    <!-- Lista de Processos (Cards) -->
    <div class="space-y-6">
        @forelse($processes as $process)
            <div class="bg-white shadow-sm border border-gray-200 rounded-xl overflow-hidden">
                
                <!-- Topo do Card: Formato Planilha -->
                <div class="bg-gray-50 border-b border-gray-200 p-4 sm:px-6">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-center">
                        <div>
                            <span class="text-xs font-semibold text-gray-500 uppercase">Setor</span>
                            <p class="text-sm font-medium text-gray-900">{{ $process->sector }}</p>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-gray-500 uppercase">Processo (POP)</span>
                            <p class="text-sm font-bold text-blue-600">{{ $process->procedure_code }}</p>
                            <p class="text-xs text-gray-600">{{ $process->process_name }}</p>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-gray-500 uppercase">Responsáveis</span>
                            <p class="text-sm font-medium text-gray-900">
                                {{ $process->users->pluck('name')->join(' / ') ?: 'Não atribuído' }}
                            </p>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-gray-500 uppercase">Prazo Final</span>
                            <p class="text-sm font-medium {{ $process->due_date && $process->due_date->isPast() && $process->progress < 100 ? 'text-red-600 font-bold' : 'text-gray-900' }}">
                                {{ $process->due_date ? $process->due_date->format('d/m/Y') : '-' }}
                            </p>
                        </div>
                        <div class="flex items-center justify-end space-x-3">
                            @hasanyrole('admin|manager')
                                <a href="{{ route('qualidade.edit', $process->id) }}" 
                                class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-full transition-all" 
                                title="Editar Processo">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                            @endhasanyrole
                            
                            @php
                                $statusColors = [
                                    'pendente' => 'bg-gray-100 text-gray-800 border border-gray-200',
                                    'em_andamento' => 'bg-blue-100 text-blue-800 border border-blue-200',
                                    'aguardando_unidade' => 'bg-orange-100 text-orange-800 border border-orange-200',
                                    'concluido' => 'bg-green-100 text-green-800 border border-green-200',
                                    'atrasado' => 'bg-red-100 text-red-800 border border-red-200',
                                ];
                            @endphp
                            <span class="px-3 py-1 rounded-full text-xs font-medium {{ $statusColors[$process->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst(str_replace('_', ' ', $process->status)) }}
                            </span>
                            
                            <!-- Círculo de % Inspirado na Imagem -->
                            <div class="w-12 h-12 rounded-full flex items-center justify-center font-bold text-sm text-white shadow-sm transition-colors duration-500
                                {{ $process->progress == 100 ? 'bg-green-500' : 'bg-indigo-500' }}">
                                {{ $process->progress }}%
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Corpo do Card: A Linha do Tempo (Timeline) -->
                <div class="p-6 overflow-x-auto">
                    @if($process->checklists->count() > 0)
                        <div class="flex items-start min-w-[600px]">
                            @foreach($process->checklists as $index => $checklist)
                                @php
                                    // REGRA DE TRAVA VISUAL
                                    $canClick = true;
                                    if (!$checklist->is_completed) {
                                        // Bloqueia marcar se o anterior não estiver feito
                                        if ($index > 0 && !$process->checklists[$index - 1]->is_completed) {
                                            $canClick = false;
                                        }
                                    } else {
                                        // Bloqueia desmarcar se o próximo já estiver feito
                                        if ($index < ($process->checklists->count() - 1) && $process->checklists[$index + 1]->is_completed) {
                                            $canClick = false;
                                        }
                                    }
                                @endphp

                                <div class="flex-1 relative text-center group">
                                    
                                    <!-- Linha Conectora -->
                                    @if(!$loop->last)
                                        <div class="absolute top-5 left-[50%] w-full h-1.5 bg-gray-200 rounded">
                                            @if($checklist->is_completed)
                                                <div class="h-full bg-green-400 rounded transition-all duration-500 w-full"></div>
                                            @else
                                                <div class="h-full bg-transparent w-0"></div>
                                            @endif
                                        </div>
                                    @endif

                                    <!-- O Nodo (Círculo Interativo) -->
                                    <button @if($canClick) wire:click="toggleChecklist({{ $checklist->id }})" @endif
                                            class="relative z-10 w-10 h-10 mx-auto rounded-full flex items-center justify-center border-4 border-white shadow-sm transition-all duration-200 focus:outline-none
                                            @if($checklist->is_completed)
                                                bg-green-500 text-white {{ $canClick ? 'hover:scale-110 cursor-pointer' : 'cursor-not-allowed opacity-60' }}
                                            @else
                                                bg-gray-200 text-gray-500 {{ $canClick ? 'hover:bg-indigo-100 hover:text-indigo-600 hover:scale-110 cursor-pointer' : 'cursor-not-allowed opacity-40' }}
                                            @endif">
                                        
                                        @if($checklist->is_completed)
                                            <!-- Ícone de Check -->
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        @else
                                            <!-- Número do Passo -->
                                            <span class="text-sm font-semibold">{{ $index + 1 }}</span>
                                        @endif
                                    </button>

                                    <!-- Textos abaixo do Nodo -->
                                    <div class="mt-4 px-2">
                                        @if($checklist->is_completed)
                                            <p class="text-xs font-bold text-green-600 mb-1">
                                                {{ $checklist->completed_at ? $checklist->completed_at->format('d/m') : 'Feito' }}
                                            </p>
                                        @else
                                            <p class="text-xs font-medium text-gray-400 mb-1">Pendente</p>
                                        @endif
                                        
                                        <h4 class="text-[11px] uppercase font-bold transition-all duration-200
                                            {{ $checklist->is_completed ? 'text-green-600 line-through opacity-70' : 'text-gray-500' }}">
                                            {{ $checklist->description }}
                                        </h4>
                                    </div>

                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6 text-gray-400 text-sm">
                            Nenhum checklist cadastrado para este processo ainda.
                        </div>
                    @endif
                </div>

            </div>
        @empty
            <div class="text-center py-12 bg-white rounded-xl shadow-sm border border-gray-200">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhum processo</h3>
                <p class="mt-1 text-sm text-gray-500">Você não possui processos da Qualidade pendentes no momento.</p>
            </div>
        @endforelse
    </div>
</div>