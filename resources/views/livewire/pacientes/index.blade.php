<div> 
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Pacientes</h1>
        <p class="text-sm text-gray-500 mt-1">Gestão e acompanhamento de pacientes</p>
    </div>

    <div class="mb-6 flex flex-wrap gap-4">
        
        <div class="bg-white border border-gray-200 shadow-sm rounded-xl px-5 py-3 flex items-center gap-4 min-w-[200px] flex-1 sm:flex-none">
            <div class="p-2.5 bg-blue-50 text-blue-600 rounded-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Visível</p>
                <p class="text-xl font-bold text-gray-900 leading-tight">{{ $pacientes->total() }}</p>
            </div>
        </div>

        @foreach($conveniosStats as $convenio)
            <div class="bg-white border border-gray-200 shadow-sm rounded-xl px-5 py-3 flex items-center gap-4 min-w-[200px] flex-1 sm:flex-none">
                <div class="p-2.5 bg-indigo-50 text-indigo-500 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path></svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide truncate max-w-[120px]" title="{{ $convenio->name }}">
                        {{ $convenio->name }}
                    </p>
                    <p class="text-xl font-bold text-gray-900 leading-tight">{{ $convenio->patients_count }}</p>
                </div>
            </div>
        @endforeach

    </div> <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
        
        @if (session()->has('message'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" 
                class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg relative flex items-center gap-2" role="alert">
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
                        placeholder="Pesquisar pacientes..." 
                        class="block w-full sm:w-64 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    >
                    <a href="{{ route('pacientes.create') }}" class="whitespace-nowrap bg-blue-600 text-white px-4 py-2 rounded-md font-semibold text-sm hover:bg-blue-700 transition-colors">
                        Cadastrar Paciente
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 relative">
                
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
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Convênio</label>
                    <select wire:model.live="agreement_id" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">Todos</option>
                        @foreach($conveniosFiltro as $convenio)
                            <option value="{{ $convenio->id }}">{{ $convenio->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1 flex justify-between">
                        Registros excluídos
                        
                        @if($unit_id !== '' || $agreement_id !== '' || $trashed_filter !== '' || $search !== '')
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

        @if(count($selected) > 0)
            <div class="bg-blue-50 border-b border-blue-100 px-4 py-3 flex items-center justify-between">
                <span class="text-sm font-medium text-blue-900">
                    {{ count($selected) }} {{ count($selected) === 1 ? 'paciente selecionado' : 'pacientes selecionados' }}
                </span>
                <div class="flex items-center gap-2">
                    <button type="button" wire:click="openSaidaModal"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-600 text-white text-xs font-semibold rounded-md hover:bg-red-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        Registrar Saída
                    </button>
                    <button type="button" wire:click="$set('selected', [])"
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
                        <th class="py-3 px-4">Paciente</th>
                        <th class="py-3 px-4">Carteira</th>
                        <th class="py-3 px-4">Unidade</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-center">Ações</th>
                    </tr>   
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                    @foreach ($pacientes as $paciente)
                        <tr class="hover:bg-gray-50 transition-colors {{ $paciente->trashed() ? 'bg-red-50/30' : '' }}">
                            <td class="py-3 px-4">
                                <input type="checkbox" wire:model.live="selected" value="{{ $paciente->id }}" class="rounded border-gray-300">
                            </td>

                            <td class="py-3 px-4">
                                <div class="font-bold text-gray-900">
                                    {{ $paciente->name }}
                                    @if($paciente->trashed())
                                        <span class="ml-2 px-2 py-0.5 bg-red-100 text-red-700 text-[10px] font-bold rounded-full uppercase tracking-wider">
                                            Excluído
                                        </span>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-400 mt-0.5">{{ $paciente->cpf }} | {{ $paciente->birth_date ? \Carbon\Carbon::parse($paciente->birth_date)->format('d/m/Y') : 'Data não informada' }}</div>
                            </td>

                            <td class="py-3 px-4 font-mono text-xs text-gray-700">{{ $paciente->agreement_number }}</td>

                            <td class="py-3 px-4">
                                <div class="text-gray-900">{{ $paciente->unit?->city }}</div>
                                <div class="text-xs text-gray-400 mt-0.5">{{ $paciente->agreement?->name }}</div>
                            </td>

                            <td class="py-4 px-6 text-sm">
                                @if($paciente->trashed())
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
                                @if($paciente->trashed())
                                    <div class="flex items-center justify-center gap-3">
                                        <button wire:click="restorePatient({{ $paciente->id }})" wire:confirm="Deseja restaurar este paciente?" class="inline-flex items-center text-green-600 hover:text-green-800 font-semibold text-sm">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                                            Restaurar
                                        </button>
                                        
                                        <button wire:click="forceDeletePatient({{ $paciente->id }})" wire:confirm="ATENÇÃO: Esta ação é irreversível e excluirá o paciente definitivamente do banco de dados. Deseja continuar?" class="inline-flex items-center text-red-600 hover:text-red-800 font-semibold text-sm">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            Excluir
                                        </button>
                                    </div>
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
                                                <a href="{{ route('pacientes.edit', $paciente->id) }}" wire:navigate class="group flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-blue-600 transition-colors">
                                                    <svg class="mr-3 h-5 w-5 text-gray-400 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                                    Editar
                                                </a>
                                                
                                                <a href="{{ route('pacientes.agenda', $paciente->id) }}" class="group flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-blue-600 transition-colors">
                                                    <svg class="mr-3 h-5 w-5 text-gray-400 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                    Agenda
                                                </a>
                                                
                                                <a href="{{ route('pacientes.carga-horaria', $paciente->id) }}" class="group flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-blue-600 transition-colors">
                                                    <svg class="mr-3 h-5 w-5 text-gray-400 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                                    Cargas Horárias
                                                </a>
                                                
                                                <button type="button" wire:click="openFrequenciaModal({{ $paciente->id }})" class="w-full text-left group flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-blue-600 transition-colors">
                                                    <svg class="mr-3 h-5 w-5 text-gray-400 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                    Emitir Frequência - Unidade
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="py-3 px-4 border-t border-gray-200">
            {{ $pacientes->links() }}
        </div>

        @if($showSaidaModal)
            <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="saida-modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 transition-opacity backdrop-blur-sm" wire:click="closeSaidaModal"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                    <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <form wire:submit.prevent="confirmarSaida">
                            <div class="bg-white px-6 pt-6 pb-4">

                                <div class="flex justify-center mb-4">
                                    <div class="bg-red-100 rounded-full p-3">
                                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </div>
                                </div>

                                <h3 class="text-lg leading-6 font-bold text-gray-900 text-center mb-1" id="saida-modal-title">
                                    Registrar Saída do(s) Paciente(s)
                                </h3>
                                <p class="text-sm text-gray-500 text-center mb-5">
                                    Os {{ count($selected) }} paciente(s) selecionado(s) ficarão inativos no sistema. O motivo abaixo será aplicado a todos eles.
                                </p>

                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Motivo principal <span class="text-red-500">*</span></label>
                                        <select wire:model="motivoSaida" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            <option value="">Selecione uma opção</option>
                                            @foreach($this->motivosSaida as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('motivoSaida') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Observação adicional (opcional)</label>
                                        <textarea wire:model="observacaoSaida" rows="3" placeholder="Detalhes sobre a alta ou saída..."
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gray-50 px-6 py-4 sm:flex sm:flex-row-reverse rounded-b-xl">
                                <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                                    Excluir
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

        @if($isFrequenciaModalOpen)
            <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 transition-opacity backdrop-blur-sm" wire:click="closeFrequenciaModal"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                    <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <form wire:submit.prevent="gerarFolhaUnimed">
                            <div class="bg-white px-6 pt-6 pb-4">
                                <h3 class="text-lg leading-6 font-bold text-gray-900 mb-4 border-b pb-2" id="modal-title">
                                    Gerar Folha de Frequência Unimed
                                </h3>

                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Terapia <span class="text-red-500">*</span></label>
                                        <select wire:model="frequencia_therapy_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            <option value="">Selecione a terapia</option>
                                            @foreach($therapies as $therapy)
                                                <option value="{{ $therapy->id }}">{{ $therapy->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('frequencia_therapy_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Local / Tipo de Atendimento <span class="text-red-500">*</span></label>
                                        <select wire:model="frequencia_service_type_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            <option value="">Selecione o local</option>
                                            @foreach($serviceTypes as $type)
                                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('frequencia_service_type_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Escola e Turno <span class="text-red-500">*</span></label>
                                        <input type="text" wire:model="frequencia_escola_turno" placeholder="Ex: Colégio Diocesano - Matutino" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        @error('frequencia_escola_turno') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Mês das Execuções <span class="text-red-500">*</span></label>
                                        <input type="month" wire:model="frequencia_mes_execucao" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        @error('frequencia_mes_execucao') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-gray-50 px-6 py-4 sm:flex sm:flex-row-reverse rounded-b-xl">
                                <button type="submit" @click="isFrequenciaModalOpen = false" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                                    Baixar PDF
                                </button>
                                <button type="button" wire:click="closeFrequenciaModal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                    Cancelar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>