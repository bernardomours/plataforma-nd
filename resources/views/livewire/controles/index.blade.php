<div>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-900 leading-tight">
            Controles De Atividades
        </h2>
    </x-slot>

    <div class="py-8 max-w-full mx-auto sm:px-6 lg:px-8">
        
        <div class="flex justify-center mb-6">
            <div class="inline-flex bg-white rounded-xl border border-gray-200 shadow-sm p-1.5 gap-1">
                <button wire:click="setTab('todos')" class="flex items-center px-4 py-2 text-sm font-semibold rounded-lg transition-colors {{ $tab === 'todos' ? 'bg-blue-50 text-blue-600' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50' }}">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                    Todos os Registros
                </button>
                <button wire:click="setTab('atualizacoes')" class="flex items-center px-4 py-2 text-sm font-semibold rounded-lg transition-colors {{ $tab === 'atualizacoes' ? 'bg-blue-50 text-blue-600' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50' }}">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    Atualizações
                </button>
                <button wire:click="setTab('entradas_saidas')" class="flex items-center px-4 py-2 text-sm font-semibold rounded-lg transition-colors {{ $tab === 'entradas_saidas' ? 'bg-blue-50 text-blue-600' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50' }}">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                    Entradas e Saídas
                </button>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-200">
            
            <div class="p-4 border-b border-gray-100 flex justify-end">
                <div class="relative w-full md:w-1/3">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Pesquisar..." class="pl-9 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-white border-b border-gray-200">
                        <tr>
                            <th class="py-3 px-6 font-bold text-gray-900 text-xs tracking-wider uppercase">DATA/HORA</th>
                            <th class="py-3 px-6 font-bold text-gray-900 text-xs tracking-wider uppercase">Usuário</th>
                            <th class="py-3 px-6 font-bold text-gray-900 text-xs tracking-wider uppercase">Ação</th>
                            <th class="py-3 px-6 font-bold text-gray-900 text-xs tracking-wider uppercase">Onde mexeu</th>
                            <th class="py-3 px-6 font-bold text-gray-900 text-xs tracking-wider uppercase">Detalhes da Mudança</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($atividades as $atividade)
                            @php
                                $modeloOriginal = class_basename($atividade->subject_type);
                                $isMovement = $modeloOriginal === 'MovementHistory';
                                
                                // 1. Recupera as propriedades padrão
                                $props = $atividade->properties;
                                if (!is_array($props)) {
                                    $props = $props ? $props->toArray() : [];
                                }

                                // 2. O SEGREDO: Busca da nova coluna 'attribute_changes' se properties estiver vazia
                                $mudancasAtributos = $atividade->attribute_changes ?? null;
                                if ($mudancasAtributos) {
                                    if (is_string($mudancasAtributos)) {
                                        $mudancasAtributos = json_decode($mudancasAtributos, true);
                                    } elseif (is_object($mudancasAtributos) && method_exists($mudancasAtributos, 'toArray')) {
                                        $mudancasAtributos = $mudancasAtributos->toArray();
                                    }
                                    
                                    if (is_array($mudancasAtributos) && isset($mudancasAtributos['attributes'])) {
                                        $props['attributes'] = $mudancasAtributos['attributes'];
                                        $props['old'] = $mudancasAtributos['old'] ?? [];
                                    }
                                }

                                $atributos = $props['attributes'] ?? [];

                                // Determina o nome do modelo para exibir
                                $modeloNome = match($modeloOriginal) {
                                    'Patient' => 'Paciente',
                                    'Professional' => 'Profissional',
                                    'User' => 'Usuário',
                                    'MovementHistory' => 'Desligamento/Entrada',
                                    default => $modeloOriginal
                                };

                                // Pega o nome de quem sofreu a ação
                                if ($isMovement) {
                                    $subjectName = $atividade->subject?->moveable?->name ?? 'Registro';
                                } else {
                                    $subjectName = $atividade->subject?->name ?? 'Registro #' . $atividade->subject_id;
                                }

                                // Lógica de extração da Ação
                                $badgeAcao = '';
                                $badgeColor = '';

                                if ($isMovement) {
                                    $acaoClinica = $atributos['action'] ?? '';
                                    $acaoLower = mb_strtolower($acaoClinica);
                                    
                                    if ($acaoLower === 'saida' || $acaoLower === 'saída') {
                                        $badgeAcao = 'SAÍDA';
                                        $badgeColor = 'text-red-600 bg-red-50 border-red-200';
                                    } elseif ($acaoLower === 'retorno') {
                                        $badgeAcao = 'RETORNO';
                                        $badgeColor = 'text-green-600 bg-green-50 border-green-200';
                                    } else {
                                        $badgeAcao = 'MOVIMENTAÇÃO';
                                        $badgeColor = 'text-blue-600 bg-blue-50 border-blue-200';
                                    }
                                } else {
                                    $badgeAcao = match($atividade->event) {
                                        'created' => 'ENTRADA',
                                        'updated' => 'ATUALIZAÇÃO',
                                        'deleted' => 'SAÍDA',
                                        'restored' => 'RETORNO',
                                        default   => mb_strtoupper($atividade->event)
                                    };

                                    $badgeColor = match($badgeAcao) {
                                        'SAÍDA'   => 'text-red-600 bg-red-50 border-red-200',
                                        'RETORNO', 'ENTRADA' => 'text-green-600 bg-green-50 border-green-200',
                                        'ATUALIZAÇÃO' => 'text-orange-600 bg-orange-50 border-orange-200',
                                        default   => 'text-gray-600 bg-gray-50 border-gray-200'
                                    };
                                }
                            @endphp

                            <tr class="hover:bg-gray-50/80 transition-colors">
                                <td class="py-4 px-6 text-gray-700">
                                    {{ $atividade->created_at->format('d/m/Y H:i') }}
                                </td>
                                
                                <td class="py-4 px-6 text-gray-900 font-medium">
                                    {{ $atividade->causer->name ?? 'Sistema' }}
                                </td>
                                
                                <td class="py-4 px-6">
                                    <span class="px-2.5 py-1 text-[10px] font-bold uppercase rounded border {{ $badgeColor }}">
                                        {{ $badgeAcao }}
                                    </span>
                                </td>

                                <td class="py-4 px-6 text-gray-700">
                                    {{ $modeloNome }} <span class="text-gray-500">({{ mb_strtoupper($subjectName) }})</span>
                                </td>

                                <td class="py-4 px-6 text-gray-700 max-w-sm whitespace-normal text-sm">
                                    @if($isMovement && $atividade->event === 'created')
                                        <div class="flex items-center">
                                            <span class="w-2.5 h-2.5 rounded-full {{ $badgeAcao === 'SAÍDA' ? 'bg-red-500' : 'bg-green-500' }} mr-2"></span>
                                            Registro de {{ ucfirst(mb_strtolower($badgeAcao)) }} oficializado.
                                        </div>
                                    @elseif($atividade->event === 'created')
                                        Cadastro realizado no sistema.
                                    @elseif($atividade->event === 'deleted')
                                        Registro removido/movido para lixeira.
                                    @elseif($atividade->event === 'updated' && !empty($atributos))
                                        <div class="space-y-1.5 font-mono text-xs">
                                            @foreach($atributos as $key => $newValue)
                                                @if(!in_array($key, ['updated_at', 'remember_token', 'deleted_at']))
                                                    @php
                                                        $oldValue = $props['old'][$key] ?? '[vazio]';
                                                        if(is_array($oldValue)) $oldValue = json_encode($oldValue);
                                                        if(is_array($newValue)) $newValue = json_encode($newValue);
                                                    @endphp
                                                    <div class="flex flex-wrap items-center gap-1">
                                                        <span class="font-bold text-gray-900">{{ $key }}:</span> 
                                                        <span class="text-gray-500 line-through">{{ $oldValue ?: '[vazio]' }}</span> 
                                                        <span class="text-gray-800 font-bold">➜</span> 
                                                        <span class="font-semibold text-gray-900">{{ $newValue ?: '[vazio]' }}</span>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    @else
                                        {{ $atividade->description }}
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-12 text-center text-gray-500">
                                    Nenhum registro de atividade encontrado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($atividades->hasPages())
                <div class="p-4 border-t border-gray-200 bg-white">
                    {{ $atividades->links() }}
                </div>
            @endif

        </div>
    </div>
</div>