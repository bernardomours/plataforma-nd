<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Solicitações de CH</h1>
        <p class="text-sm text-gray-500 mt-1">Lista para solicitação de carga horária</p>
    </div>

    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 mb-6 flex flex-col md:flex-row gap-4 items-end">
        <div class="w-full md:w-64">
            <label class="block text-xs font-semibold text-gray-700 mb-1">Competência (Mês/Ano)</label>
            <input type="month" wire:model.live="mesReferencia" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
        </div>
        
        <div class="flex-1 w-full">
            <label class="block text-xs font-semibold text-gray-700 mb-1">Pesquisar Paciente (Opcional)</label>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Digite o nome para buscar na fila..." class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-xs text-gray-600 font-bold uppercase tracking-wider">
                        <th class="py-3 px-4">Nome do Paciente</th>
                        <th class="py-3 px-4 text-right">Ação</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-800">
                    @forelse ($this->pacientesPendentes as $paciente)
                        <tr class="hover:bg-blue-50 transition-colors">
                            <td class="py-4 px-4 font-medium uppercase">{{ $paciente->name }}</td>
                            <td class="py-4 px-4 text-right">
                                <button 
                                    type="button"
                                    wire:click="$dispatch('abrir-modal-ch', { pacienteId: {{ $paciente->id }}, mesReferencia: '{{ $mesReferencia }}' })"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-xs font-bold rounded-md hover:bg-blue-700 transition-colors shadow-sm"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    Registrar Solicitação
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="py-16 text-center">
                                <div class="flex flex-col items-center justify-center text-gray-500">
                                    <div class="w-16 h-16 bg-green-100 text-green-500 rounded-full flex items-center justify-center mb-4">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    </div>
                                    <p class="text-xl font-bold text-gray-800 mb-1">Fila Zerada!</p>
                                    <p class="text-sm">
                                        Todos os pacientes ativos já possuem Carga Horária solicitada para <br> 
                                        <strong>{{ \Carbon\Carbon::parse($mesReferencia)->format('m/Y') }}</strong>.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="py-3 px-4 border-t border-gray-200">
            {{ $this->pacientesPendentes->links() }}
        </div>
    </div>

    <livewire:ch-solicitada.registro-modal />
</div>