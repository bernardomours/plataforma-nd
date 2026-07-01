<div>
    <!-- Cabeçalho -->
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Regras de Pagamento</h1>
            <p class="text-sm text-gray-500 mt-1">Gerencie os valores de repasse para os profissionais.</p>
        </div>
        <button wire:click="abrirModalCriar" class="px-4 py-2 bg-blue-600 text-white text-sm font-bold rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2 shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Nova Regra
        </button>
    </div>

    <!-- Tabela -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-xs text-gray-800 font-bold uppercase tracking-wider">
                        <th class="py-3 px-4">Profissional</th>
                        <th class="py-3 px-4">Tipo</th>
                        <th class="py-3 px-4">Valor (R$)</th>
                        <th class="py-3 px-4 text-center">Convênio</th>
                        <th class="py-3 px-4 text-center">Terapia</th>
                        <th class="py-3 px-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($regras as $regra)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="py-4 px-4 font-medium text-gray-900">{{ $regra->professional->name ?? 'N/A' }}</td>
                            
                            <td class="py-4 px-4">
                                @if($regra->payment_type === 'por_sessao')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-green-50 text-green-700 border border-green-200">Por Sessão</span>
                                @elseif($regra->payment_type === 'por_hora')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-yellow-50 text-yellow-700 border border-yellow-200">Por Hora</span>
                                @elseif($regra->payment_type === 'por_dia')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">Por Dia</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-gray-50 text-gray-700 border border-gray-200">{{ $regra->payment_type }}</span>
                                @endif
                            </td>
                            
                            <td class="py-4 px-4 font-bold text-gray-900">R$ {{ number_format($regra->amount, 2, ',', '.') }}</td>
                            
                            <td class="py-4 px-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200">
                                    {{ $regra->agreement->name ?? 'Todos' }}
                                </span>
                            </td>

                            <td class="py-4 px-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200">
                                    {{ $regra->therapy->name ?? 'Todas' }}
                                </span>
                            </td>
                            
                            <td class="py-4 px-4 text-right">
                                <button wire:click="abrirModalEditar({{ $regra->id }})" class="text-blue-600 hover:text-blue-900 font-medium text-xs mr-3">Editar</button>
                                <button wire:click="confirmarExclusao({{ $regra->id }})" class="text-red-600 hover:text-red-900 font-medium text-xs">Excluir</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-gray-500 text-sm">
                                Nenhuma regra de pagamento cadastrada.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($regras->hasPages())
            <div class="py-3 px-4 border-t border-gray-100 bg-gray-50">
                {{ $regras->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL CRIAR/EDITAR -->
    @if($modalAberto)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" wire:click="fecharModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                
                <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-200">
                    <form wire:submit="salvar">
                        <div class="bg-white px-6 pt-5 pb-6">
                            <h3 class="text-lg leading-6 font-bold text-gray-900 mb-4 border-b pb-2">
                                {{ $regra_id ? 'Editar Regra' : 'Nova Regra de Pagamento' }}
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Detalhes Principais -->
                                <div class="col-span-2">
                                    <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2 mt-2">Detalhes da Regra</h4>
                                </div>

                                <div class="col-span-2 md:col-span-1">
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Profissional <span class="text-red-500">*</span></label>
                                    <select wire:model="professional_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        <option value="">Selecione um profissional...</option>
                                        @foreach($profissionais as $prof)
                                            <option value="{{ $prof->id }}">{{ $prof->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('professional_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div class="col-span-2 md:col-span-1">
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Tipo de Pagamento <span class="text-red-500">*</span></label>
                                    <select wire:model="payment_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        <option value="por_sessao">Por Sessão (Padrão)</option>
                                        <option value="por_hora">Por Hora (Ex: Humana/ABA)</option>
                                        <option value="por_dia">Por Dia (Ex: Fono)</option>
                                    </select>
                                    @error('payment_type') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div class="col-span-2 md:col-span-1">
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Valor (R$) <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 sm:text-sm">R$</span>
                                        </div>
                                        <input type="text" wire:model="amount" class="w-full pl-9 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="0.00">
                                    </div>
                                    @error('amount') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <!-- Filtros de Exceção -->
                                <div class="col-span-2 mt-4">
                                    <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Filtros de Exceção</h4>
                                    <p class="text-xs text-gray-400 mb-3">Deixe em branco para aplicar a todos os convênios ou terapias deste profissional.</p>
                                </div>

                                <div class="col-span-2 md:col-span-1">
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Convênio Específico</label>
                                    <select wire:model="agreement_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        <option value="">Aplicar a Todos</option>
                                        @foreach($convenios as $conv)
                                            <option value="{{ $conv->id }}">{{ $conv->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-span-2 md:col-span-1">
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Terapia Específica</label>
                                    <select wire:model="therapy_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        <option value="">Aplicar a Todas</option>
                                        @foreach($terapias as $terapia)
                                            <option value="{{ $terapia->id }}">{{ $terapia->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse rounded-b-xl border-t border-gray-100">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 sm:ml-3 sm:w-auto sm:text-sm">
                                Salvar Regra
                            </button>
                            <button type="button" wire:click="fecharModal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL DE EXCLUSÃO -->
    @if($modalExclusaoAberto)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" wire:click="$set('modalExclusaoAberto', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-200">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-bold text-gray-900" id="modal-title">Excluir Regra</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">Tem certeza que deseja excluir esta regra de pagamento? Esta ação não pode ser desfeita e pode afetar apurações futuras.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-100 rounded-b-xl">
                        <button type="button" wire:click="excluir" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm">
                            Excluir Regra
                        </button>
                        <button type="button" wire:click="$set('modalExclusaoAberto', false)" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>