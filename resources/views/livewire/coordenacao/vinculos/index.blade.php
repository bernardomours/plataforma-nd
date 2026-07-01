<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Vínculos de Pacientes</h1>
        <p class="text-sm text-gray-500 mt-1">Visualização de coordenadores/supervisores e seus pacientes</p>
    </div>

    <div class="max-w-full mx-auto py-6 sm:px-6 lg:px-8">
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6 p-6">
            <h3 class="font-bold text-gray-900 text-base mb-1">Filtro de Coordenadores/Supervisores</h3>
            <p class="text-sm text-gray-500 mb-6">Selecione um profissional para visualizar todos os pacientes sob sua responsabilidade.</p>
            
            <div class="w-full">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Coordenador / Supervisor</label>
                <select wire:model.live="profissional_id" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="">Selecione uma opção</option>
                    @foreach($profissionais as $prof)
                        <option value="{{ $prof->id }}">{{ $prof->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if(empty($profissional_id))
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 py-16 flex flex-col items-center justify-center text-center">
                <div class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center mb-4 border border-gray-100">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-1">Nenhum profissional selecionado</h3>
                <p class="text-sm text-gray-500">Selecione um profissional acima para ver a lista de pacientes.</p>
            </div>
        
        @else
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6 p-5 flex justify-between items-center">
                <h3 class="font-bold text-lg text-gray-900">Total de Pacientes Vinculados:</h3>
                <span class="text-4xl font-extrabold text-blue-600">{{ $totalVinculos }}</span>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                
                <div class="p-4 border-b border-gray-100 flex justify-end bg-gray-50/50">
                    <div class="relative w-64">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Pesquisar..." class="block w-full pl-10 border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                </div>

                <div class="overflow-x-auto relative" wire:loading.class="opacity-50 pointer-events-none">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr class="bg-white border-b border-gray-200 text-xs text-gray-800 font-bold">
                                <th class="py-4 px-6">Paciente</th>
                                <th class="py-4 px-6">Ambiente</th>
                                <th class="py-4 px-6">Coordenador</th>
                                <th class="py-4 px-6">Supervisor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($vinculos as $vinculo)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="py-4 px-6 uppercase text-sm text-gray-700">{{ $vinculo->patient->name ?? '-' }}</td>
                                    <td class="py-4 px-6">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-600 border border-blue-200">
                                            {{ $vinculo->serviceType->name ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-sm text-gray-700">{{ $vinculo->coordinator->name ?? '-' }}</td>
                                    <td class="py-4 px-6 text-sm text-gray-700">{{ $vinculo->supervisor->name ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-10 text-center text-gray-500 text-sm">
                                        Nenhum paciente encontrado para este profissional.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($vinculos && $vinculos->hasPages())
                    <div class="py-3 px-6 border-t border-gray-100 bg-gray-50">
                        {{ $vinculos->links() }}
                    </div>
                @endif
            </div>
        @endif

    </div>
</div>