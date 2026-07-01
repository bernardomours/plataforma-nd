<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Cronograma de Terapias ABA/DENVER</h1>
        <p class="text-sm text-gray-500 mt-1">Monitoramento e histórico de coordenações e supervisões ABA/DENVER</p>
    </div>

    <div class="max-w-full mx-auto py-6 sm:px-6 lg:px-8">
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
            <div class="p-5 border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-bold text-gray-800">Filtros</h3>
                <button wire:click="limparFiltros" class="text-sm font-medium text-red-600 hover:text-red-800 transition-colors">Limpar filtros</button>
            </div>
            
            <div class="p-5">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Ambiente</label>
                        <select wire:model="ambiente_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                            <option value="">Todos os Ambientes</option>
                            @foreach($ambientes as $amb)
                                <option value="{{ $amb->id }}">{{ $amb->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Unidade</label>
                        <select wire:model="unidade_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                            <option value="">Todos</option>
                            @foreach($unidades as $u)
                                <option value="{{ $u->id }}">{{ $u->city ?? $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Profissional</label>
                        <select wire:model="profissional_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                            <option value="">Todos</option>
                            @foreach($profissionais as $prof)
                                <option value="{{ $prof->id }}">{{ $prof->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Status Coordenação</label>
                        <select wire:model="status_coordenacao" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                            <option value="">Todos</option>
                            <option value="em_dia">✅ Em dia (0 dias)</option>
                            <option value="pendente">⚠️ Visita Pendente</option>
                            <option value="sem_coordenador">🚨 Sem coordenador cadastrado</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Status Supervisão</label>
                        <select wire:model="status_supervisao" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                            <option value="">Todos</option>
                            <option value="em_dia">✅ Em dia (0 dias)</option>
                            <option value="pendente">⚠️ Visita Pendente</option>
                            <option value="sem_supervisor">🚨 Sem supervisor cadastrado</option>
                        </select>
                    </div>
                </div>
                <button wire:click="aplicarFiltros" wire:loading.attr="disabled" class="px-5 py-2 bg-blue-500 text-white font-semibold text-sm rounded-md hover:bg-blue-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center min-w-[140px]">
                    <span wire:loading.remove wire:target="aplicarFiltros">
                        Aplicar filtros
                    </span>
                    <span wire:loading wire:target="aplicarFiltros" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Calculando...
                    </span>
                </button>
            </div>
        </div>

        <div class="flex justify-end mb-4">
            <div class="relative w-64">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Pesquisar..." class="block w-full pl-10 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-xs text-gray-800 font-bold">
                            <th class="py-3 px-4">Paciente</th>
                            <th class="py-3 px-4 w-24">Terapia</th>
                            <th class="py-3 px-4 w-1/3">Coordenação (Meta: 10)</th>
                            <th class="py-3 px-4 w-1/3">Supervisão (Meta: 20)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($linhas as $linha)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="py-4 px-4">
                                    <div class="font-bold text-gray-800 uppercase text-xs mb-1">{{ $linha->paciente->name ?? 'N/A' }}</div>
                                    <div class="text-xs text-gray-500 mb-2">{{ $linha->ambiente->name ?? 'N/A' }}</div>
                                    
                                    <button wire:click="openHistory({{ $linha->paciente->id }}, {{ $linha->ambiente->id ?? 'null' }})" class="text-blue-500 hover:text-blue-700 text-[11px] font-semibold inline-flex items-center gap-1 transition-colors bg-blue-50 hover:bg-blue-100 px-2 py-1 rounded">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        Ver histórico
                                    </button>
                                </td>
                                <td class="py-4 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                                        {{ $linha->terapia->name ?? 'ABA' }}
                                    </span>
                                </td>
                                
                                <td class="py-4 px-4">
                                    <div class="flex flex-col items-start gap-1">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold border {{ $linha->coord->badge['color'] }}">
                                            {{ $linha->coord->badge['label'] }}
                                        </span>
                                        <span class="text-xs text-gray-500 mt-1">
                                            {{ $linha->coord->ultima_string }}
                                        </span>
                                    </div>
                                </td>

                                <td class="py-4 px-4">
                                    <div class="flex flex-col items-start gap-1">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold border {{ $linha->superv->badge['color'] }}">
                                            {{ $linha->superv->badge['label'] }}
                                        </span>
                                        <span class="text-xs text-gray-500 mt-1">
                                            {{ $linha->superv->ultima_string }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-gray-500 text-sm">
                                    Nenhum monitoramento encontrado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="py-3 px-4 border-t border-gray-100 bg-gray-50">
                {{ $linhas->links() }}
            </div>
        </div>

    </div>
    @if($isHistoryModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeHistoryModal"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-5xl sm:w-full border border-gray-200">
                    
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex justify-between items-center mb-6 border-b border-gray-100 pb-4">
                            <h3 class="text-xl leading-6 font-bold text-gray-900" id="modal-title">
                                Histórico de Coordenação/Supervisão <br>
                                <span class="text-sm font-medium text-gray-500 uppercase">{{ $historyData['paciente'] }}</span>
                            </h3>
                            <button wire:click="closeHistoryModal" class="text-gray-400 hover:text-gray-500 bg-gray-50 p-2 rounded-full hover:bg-gray-100 transition-colors">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 relative">
                            
                            <div class="hidden md:block absolute left-1/2 top-0 bottom-0 w-px bg-gray-100 -translate-x-1/2"></div>

                            <div>
                                <div class="flex items-center gap-2 mb-4 justify-center md:justify-start">
                                    <div class="p-1.5 bg-blue-50 text-blue-600 rounded-lg shadow-sm border border-blue-100">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                    </div>
                                    <h4 class="font-bold text-gray-800 text-lg tracking-tight">COORDENAÇÃO</h4>
                                </div>
                                
                                <div class="space-y-3 max-h-[50vh] overflow-y-auto pr-2 custom-scrollbar">
                                    @forelse($historyData['coordenacao'] as $visita)
                                        <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm hover:border-blue-300 transition-colors">
                                            <div class="flex justify-between items-start mb-2">
                                                <span class="text-xs font-bold text-blue-700 bg-blue-50 px-2.5 py-1 rounded border border-blue-100 flex items-center gap-1">
                                                    {{ $visita->therapy->name ?? 'N/A' }} 
                                                    <span class="text-blue-300">•</span> 
                                                    {{ $visita->serviceType->name ?? 'N/A' }}
                                                </span>
                                                <span class="text-xs text-gray-500 font-semibold bg-gray-50 px-2 py-1 rounded border border-gray-100 flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                    {{ \Carbon\Carbon::parse($visita->happened_at)->format('d/m/Y') }}
                                                </span>
                                            </div>
                                            <div class="text-sm text-gray-800 mt-2 flex items-center gap-2">
                                                <div class="w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-500">
                                                    {{ substr($visita->professional->name ?? 'D', 0, 1) }}
                                                </div>
                                                <span class="font-medium">{{ $visita->professional->name ?? 'Desconhecido' }}</span>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="text-sm text-gray-400 italic text-center py-8 bg-gray-50 rounded-lg border border-dashed border-gray-200">
                                            Nenhum histórico de coordenação encontrado.
                                        </div>
                                    @endforelse
                                </div>
                            </div>

                            <div>
                                <div class="flex items-center gap-2 mb-4 justify-center md:justify-start">
                                    <div class="p-1.5 bg-purple-50 text-purple-600 rounded-lg shadow-sm border border-purple-100">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                    </div>
                                    <h4 class="font-bold text-gray-800 text-lg tracking-tight">SUPERVISÃO</h4>
                                </div>
                                
                                <div class="space-y-3 max-h-[50vh] overflow-y-auto pr-2 custom-scrollbar">
                                    @forelse($historyData['supervisao'] as $visita)
                                        <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm hover:border-purple-300 transition-colors">
                                            <div class="flex justify-between items-start mb-2">
                                                <span class="text-xs font-bold text-purple-700 bg-purple-50 px-2.5 py-1 rounded border border-purple-100 flex items-center gap-1">
                                                    {{ $visita->therapy->name ?? 'N/A' }} 
                                                    <span class="text-purple-300">•</span> 
                                                    {{ $visita->serviceType->name ?? 'N/A' }}
                                                </span>
                                                <span class="text-xs text-gray-500 font-semibold bg-gray-50 px-2 py-1 rounded border border-gray-100 flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                    {{ \Carbon\Carbon::parse($visita->happened_at)->format('d/m/Y') }}
                                                </span>
                                            </div>
                                            <div class="text-sm text-gray-800 mt-2 flex items-center gap-2">
                                                <div class="w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-500">
                                                    {{ substr($visita->professional->name ?? 'D', 0, 1) }}
                                                </div>
                                                <span class="font-medium">{{ $visita->professional->name ?? 'Desconhecido' }}</span>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="text-sm text-gray-400 italic text-center py-8 bg-gray-50 rounded-lg border border-dashed border-gray-200">
                                            Nenhum histórico de supervisão encontrado.
                                        </div>
                                    @endforelse
                                </div>
                            </div>

                        </div>
                    </div>
                    
                    <div class="bg-gray-50 px-6 py-4 sm:flex sm:flex-row-reverse border-t border-gray-100 rounded-b-xl">
                        <button wire:click="closeHistoryModal" type="button" class="w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-5 py-2.5 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:w-auto transition-colors">
                            Fechar Visualização
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>