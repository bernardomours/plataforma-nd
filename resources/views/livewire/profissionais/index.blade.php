<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Profissionais</h1>
        <p class="text-sm text-gray-500 mt-1">Gestão da equipe clínica e administrativa</p>
    </div>

    <div class="mb-6 flex flex-wrap gap-4">
        <div class="bg-white border border-gray-200 shadow-sm rounded-xl px-5 py-3 flex items-center gap-4 min-w-[200px] flex-1 sm:flex-none">
            <div class="p-2.5 bg-blue-50 text-blue-600 rounded-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Visível</p>
                <p class="text-xl font-bold text-gray-900 leading-tight">{{ $profissionais->total() }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
        
        @if (session()->has('message'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" 
                class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg relative flex items-center gap-2 m-4" role="alert">
                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span class="block sm:inline font-medium text-sm">{{ session('message') }}</span>
            </div>
        @endif

        <div class="bg-gray-50/50 p-4 border-b border-gray-200">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-4">
                <h3 class="text-sm font-bold text-gray-700">Filtros</h3>
                
                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <input 
                        wire:model.live="search" 
                        type="text" 
                        placeholder="Pesquisar por nome ou CPF..." 
                        class="block w-full sm:w-64 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    >
                    <a href="{{ route('profissionais.create') }}" class="whitespace-nowrap bg-blue-600 text-white px-4 py-2 rounded-md font-semibold text-sm hover:bg-blue-700 transition-colors">
                        Cadastrar Profissional
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 relative">
                
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Unidade</label>
                    <select wire:model.live="unit_id" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">Todas</option>
                        @foreach($unidadesFiltro as $unidade)
                            <option value="{{ $unidade->id }}">{{ $unidade->city ?? $unidade->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Especialidade (Terapia)</label>
                    <select wire:model.live="therapy_id" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">Todas</option>
                        @foreach($terapiasFiltro as $terapia)
                            <option value="{{ $terapia->id }}">{{ $terapia->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Cargo/Função</label>
                    <select wire:model.live="role" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">Todos</option>
                        @foreach($cargosFiltro as $cargo)
                            <option value="{{ $cargo->value }}">{{ $cargo->getLabel() }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1 flex justify-between">
                        Registros excluídos
                        @if($unit_id !== '' || $therapy_id !== '' || $role !== '' || $trashed_filter !== '' || $search !== '')
                            <button type="button" wire:click="clearFilters" class="text-red-600 hover:text-red-800 transition-colors cursor-pointer">
                                Limpar filtros
                            </button>
                        @endif
                    </label>
                    <select wire:model.live="trashed_filter" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">Não exibir registros excluídos</option>
                        <option value="with_trashed">Exibir registros excluídos</option>
                        <option value="only_trashed">Apenas registros excluídos</option>
                    </select>
                </div>

            </div>
        </div>

        @if(count($selectedProfessionals) > 0)
            <div class="bg-blue-50 border-b border-blue-100 px-4 py-3 flex items-center justify-between">
                <span class="text-sm font-medium text-blue-900">
                    {{ count($selectedProfessionals) }} {{ count($selectedProfessionals) === 1 ? 'profissional selecionado' : 'profissionais selecionados' }}
                </span>
                <div class="flex items-center gap-2">
                    <button type="button" wire:click="openSaidaModal"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-600 text-white text-xs font-semibold rounded-md hover:bg-red-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        Registrar Saída
                    </button>
                    <button type="button" wire:click="$set('selectedProfessionals', [])"
                        class="text-xs text-blue-700 hover:text-blue-900 font-medium px-2">
                        Cancelar seleção
                    </button>
                </div>
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase tracking-wider text-gray-500 font-semibold">
                        <th class="py-3 px-4 w-10">
                            <input type="checkbox" wire:model.live="selectAll" class="rounded border-gray-300">
                        </th>
                        <th class="py-3 px-4">Profissional</th>
                        <th class="py-3 px-4">Cargo / Terapias</th>
                        <th class="py-3 px-4">Unidades</th>
                        <th class="py-3 px-4">Status</th>
                    </tr>   
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                    @forelse ($profissionais as $profissional)
                        <tr class="hover:bg-gray-50 transition-colors {{ $profissional->trashed() ? 'opacity-60 bg-gray-50' : '' }}">
                            
                            <td class="py-3 px-4">
                                @if(!$profissional->trashed())
                                    <input type="checkbox" wire:model.live="selectedProfessionals" value="{{ $profissional->id }}" class="rounded border-gray-300">
                                @else
                                    <input type="checkbox" disabled class="rounded border-gray-200 bg-gray-100 cursor-not-allowed">
                                @endif
                            </td>

                            <td class="py-3 px-4">
                                <div class="font-bold text-gray-900">{{ $profissional->name }}</div>
                                <div class="text-xs text-gray-400 mt-0.5">{{ $profissional->cpf }} | {{ $profissional->phone }}</div>
                            </td>

                            <td class="py-3 px-4">
                                <div class="font-medium text-gray-700 mb-1">{{ $profissional->role?->getLabel() }}</div>
                                <div class="flex flex-wrap gap-1">
                                    @forelse($profissional->therapies as $terapia)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-600 border border-blue-200 uppercase tracking-wider">
                                            {{ $terapia->name }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-gray-400">-</span>
                                    @endforelse
                                </div>
                            </td>

                            <td class="py-3 px-4">
                                <div class="flex flex-wrap gap-1">
                                    @forelse($profissional->units as $unidade)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium text-gray-600 bg-gray-100 border border-gray-200">
                                            {{ $unidade->city ?? $unidade->name }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-gray-400">-</span>
                                    @endforelse
                                </div>
                            </td>

                            <td class="py-4 px-6 text-sm">
                                @if($profissional->trashed())
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-red-50 text-red-700 border border-red-100">
                                        Inativo
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-green-50 text-green-700 border border-green-100">
                                        Ativo
                                    </span>
                                @endif
                            </td>

                            <td class="py-3 px-4 text-center">
                                @if($profissional->trashed())
                                    <button wire:click="openRetornoModal({{ $profissional->id }})" title="Registrar Retorno" class="text-green-600 hover:text-green-800 bg-green-50 p-1.5 rounded-md hover:bg-green-100 transition border border-green-200">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                    </button>
                                @else
                                    <div x-data="{ open: false }" @click.outside="open = false" class="relative inline-block text-left">
                                        <button @click="open = !open" class="text-gray-400 hover:text-gray-600 focus:outline-none p-1 rounded-full hover:bg-gray-100 transition-colors">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path></svg>
                                        </button>

                                        <div x-show="open" 
                                            x-transition:enter="transition ease-out duration-100" 
                                            x-transition:enter-start="transform opacity-0 scale-95" 
                                            x-transition:enter-end="transform opacity-100 scale-100" 
                                            x-transition:leave="transition ease-in duration-75" 
                                            x-transition:leave-start="transform opacity-100 scale-100" 
                                            x-transition:leave-end="transform opacity-0 scale-95" 
                                            class="absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50 divide-y divide-gray-50"
                                            style="display: none;">
                                            
                                            <div class="py-1">
                                                <a href="{{ route('profissionais.edit', $profissional->id) }}" wire:navigate class="group flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-blue-600 transition-colors">
                                                    <svg class="mr-3 h-5 w-5 text-gray-400 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                                    Editar
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 px-4 text-center text-gray-500">Nenhum profissional encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="py-3 px-4 border-t border-gray-200">
            {{ $profissionais->links() }}
        </div>
    </div>

    @if($isSaidaModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-600 bg-opacity-50 transition-opacity backdrop-blur-sm" wire:click="closeSaidaModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form wire:submit.prevent="registrarSaida">
                        <div class="bg-white px-6 pt-6 pb-4">
                            <div class="flex justify-center mb-4">
                                <div class="bg-red-100 rounded-full p-3">
                                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </div>
                            </div>

                            <h3 class="text-lg leading-6 font-bold text-gray-900 text-center mb-1" id="modal-title">
                                Registrar Saída ({{ count($selectedProfessionals) }} profissional(is))
                            </h3>
                            <p class="text-sm text-gray-500 text-center mb-5">
                                Os profissionais selecionados ficarão inativos no sistema. O motivo abaixo será salvo no histórico.
                            </p>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Motivo da Saída <span class="text-red-500">*</span></label>
                                    <input type="text" wire:model="motivo_saida" placeholder="Ex: Desligamento, Término de Contrato..." class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm">
                                    @error('motivo_saida') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Observação Adicional (Opcional)</label>
                                    <textarea wire:model="observacao_saida" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm"></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 px-6 py-4 sm:flex sm:flex-row-reverse rounded-b-xl">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                                Confirmar Saída
                            </button>
                            <button type="button" wire:click="closeSaidaModal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if($isRetornoModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-600 bg-opacity-50 transition-opacity backdrop-blur-sm" wire:click="closeRetornoModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form wire:submit.prevent="registrarRetorno">
                        <div class="bg-white px-6 pt-6 pb-4">
                            <div class="flex justify-center mb-4">
                                <div class="bg-green-100 rounded-full p-3">
                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                </div>
                            </div>
                            
                            <h3 class="text-lg leading-6 font-bold text-gray-900 text-center mb-1" id="modal-title">
                                Registrar Retorno
                            </h3>
                            <p class="text-sm text-gray-500 text-center mb-5">
                                Este profissional será reativado no sistema e o evento constará no histórico.
                            </p>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Motivo do Retorno <span class="text-red-500">*</span></label>
                                    <input type="text" wire:model="motivo_retorno" placeholder="Ex: Novo contrato assinado..." class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm">
                                    @error('motivo_retorno') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 px-6 py-4 sm:flex sm:flex-row-reverse rounded-b-xl">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                                Confirmar Retorno
                            </button>
                            <button type="button" wire:click="closeRetornoModal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>