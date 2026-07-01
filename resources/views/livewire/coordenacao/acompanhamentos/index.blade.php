<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Acompanhamentos</h1>
        <p class="text-sm text-gray-500 mt-1">Acompanhamentos de coordenações e supervisões pendentes e realizadas</p>
    </div>

    <div class="max-w-full mx-auto py-6 sm:px-6 lg:px-8">
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6 relative">
            <div class="absolute top-4 right-4">
                <button wire:click="limparFiltros" class="text-sm font-medium text-red-600 hover:text-red-800 transition-colors">Limpar filtros</button>
            </div>
            <div class="p-5 border-b border-gray-100">
                <h3 class="font-bold text-gray-800">Filtros</h3>
            </div>
            <div class="p-5 grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Mês</label>
                    <select wire:model.live="mes" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                        <option value="">Selecione uma opção</option>
                        <option value="1">Janeiro</option><option value="2">Fevereiro</option><option value="3">Março</option><option value="4">Abril</option><option value="5">Maio</option><option value="6">Junho</option><option value="7">Julho</option><option value="8">Agosto</option><option value="9">Setembro</option><option value="10">Outubro</option><option value="11">Novembro</option><option value="12">Dezembro</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Ano</label>
                    <select wire:model.live="ano" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                        <option value="">Selecione uma opção</option>
                        @foreach($anosDisponiveis as $a) <option value="{{ $a }}">{{ $a }}</option> @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Tipo</label>
                    <select wire:model.live="tipo" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                        <option value="">Todos</option>
                        <option value="coordination">Coordenação</option>
                        <option value="supervision">Supervisão</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Status</label>
                    <select wire:model.live="status" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                        <option value="">Todos</option>
                        <option value="pending">Pendente</option>
                        <option value="completed">Concluída</option>
                        <option value="cancelled">Cancelada</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Profissional</label>
                    <select wire:model.live="profissional_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                        <option value="">Todos</option>
                        @foreach($profissionais as $prof) <option value="{{ $prof->id }}">{{ $prof->name }}</option> @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="flex justify-between items-center mb-4">
            <div>
                @if(count($selectedVisits) > 0)
                    <button wire:click="deleteSelected" wire:confirm="Tem a certeza que deseja excluir os registros selecionados?" class="px-4 py-2 bg-red-50 text-red-600 text-sm font-semibold rounded-md hover:bg-red-100 border border-red-200 transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        Excluir Selecionados ({{ count($selectedVisits) }})
                    </button>
                @endif
            </div>
            <div class="relative w-64">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Pesquisar..." class="block w-full pl-10 border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-xs text-gray-800 font-bold">
                            <th class="py-3 px-4 w-10">
                                <input type="checkbox" wire:model.live="selectAll" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </th>
                            <th class="py-3 px-4">Paciente</th>
                            <th class="py-3 px-4">Terapia</th>
                            <th class="py-3 px-4">Profissional</th>
                            <th class="py-3 px-4">Realizada em</th>
                            <th class="py-3 px-4">Tipo</th>
                            <th class="py-3 px-4">Ambiente</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($visits as $visit)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="py-3 px-4">
                                    <input type="checkbox" value="{{ $visit->id }}" wire:model.live="selectedVisits" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                </td>
                                <td class="py-3 px-4 font-medium uppercase text-xs">{{ $visit->patient->name ?? '-' }}</td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">{{ $visit->therapy->name ?? '-' }}</span>
                                </td>
                                <td class="py-3 px-4 text-xs uppercase">{{ $visit->professional->name ?? '-' }}</td>
                                <td class="py-3 px-4 text-xs">{{ $visit->happened_at ? \Carbon\Carbon::parse($visit->happened_at)->format('d/m/Y') : '-' }}</td>
                                <td class="py-3 px-4 text-blue-600 text-xs font-medium">
                                    {{ $visit->type instanceof \App\Enums\VisitType ? $visit->type->getLabel() : match($visit->type) { 'coordination' => 'Coordenação', 'supervision' => 'Supervisão', default => $visit->type } }}
                                </td>
                                <td class="py-3 px-4 text-orange-600 text-xs font-medium">{{ $visit->serviceType->name ?? '-' }}</td>
                                <td class="py-3 px-4">
                                    @if($visit->status === 'pending' || (is_object($visit->status) && $visit->status->value === 'pending'))
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200">Pendente</span>
                                    @elseif($visit->status === 'cancelled' || (is_object($visit->status) && $visit->status->value === 'cancelled'))
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-50 text-red-700 border border-red-200">Cancelada</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-50 text-green-700 border border-green-200">Concluída</span>
                                    @endif
</td>
                                <td class="py-3 px-4 text-right">
                                    <button wire:click="editVisit({{ $visit->id }})" class="text-blue-600 hover:text-blue-900 bg-blue-50 p-1.5 rounded-md hover:bg-blue-100 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-6 text-center text-gray-500 text-sm">Nenhum acompanhamento encontrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="py-3 px-4 border-t border-gray-100 bg-gray-50">
                {{ $visits->links() }}
            </div>
        </div>

    </div>

    @if($isEditModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeModal"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full border border-gray-200">
                    
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex justify-between items-center mb-5 border-b border-gray-100 pb-3">
                            <h3 class="text-xl leading-6 font-bold text-gray-900" id="modal-title">Editar Acompanhamento</h3>
                            <button wire:click="closeModal" class="text-gray-400 hover:text-gray-500">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>

                        <form wire:submit.prevent="salvarVisit">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Paciente <span class="text-red-500">*</span></label>
                                    <input type="text" wire:model="formPacienteNome" disabled class="block w-full bg-gray-50 border-gray-300 rounded-md shadow-sm text-gray-500 sm:text-sm cursor-not-allowed">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Profissional (Coord/Superv)</label>
                                    <select wire:model="formProfissionalId" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        <option value="">Selecione...</option>
                                        @foreach($profissionais as $prof)
                                            <option value="{{ $prof->id }}">{{ $prof->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Data da Visita</label>
                                    <input type="date" wire:model="formHappenedAt" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    @error('formHappenedAt') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Tipo <span class="text-red-500">*</span></label>
                                    <select wire:model="formTipo" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        <option value="coordination">Coordenação</option>
                                        <option value="supervision">Supervisão</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                                    <select wire:model="formStatus" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        <option value="pending">Pendente</option>
                                        <option value="completed">Concluído</option>
                                        <option value="cancelled">Cancelado</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Ambiente / Tipo de Serviço <span class="text-red-500">*</span></label>
                                    <select wire:model="formServiceTypeId" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        <option value="">Selecione...</option>
                                        @foreach($ambientes as $amb)
                                            <option value="{{ $amb->id }}">{{ $amb->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Terapia (Referente à Visita) <span class="text-red-500">*</span></label>
                                    <select wire:model="formTherapyId" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        <option value="">Selecione...</option>
                                        @foreach($terapias as $terapia)
                                            <option value="{{ $terapia->id }}">{{ $terapia->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Observações</label>
                                    <textarea wire:model="formNotes" rows="3" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
                                </div>

                            </div>
                        </form>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-100">
                        <button wire:click="salvarVisit" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-500 text-base font-medium text-white hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                            Salvar alterações
                        </button>
                        <button wire:click="closeModal" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>