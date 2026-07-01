<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Avaliações Neuro</h1>
        <p class="text-sm text-gray-500 mt-1">Registro e acompanhamento das avaliações neuro</p>
    </div>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div class="w-full sm:w-1/3">
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Pesquisar por paciente..." class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <a href="{{ route('avaliacoes-neuro.create') }}" wire:navigate class="inline-flex items-center px-4 py-2 bg-blue-500 border border-transparent rounded-lg font-semibold text-sm text-white hover:bg-blue-600 focus:bg-blue-600 active:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150 whitespace-nowrap">
                        Registrar Avaliação
                    </a>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-200">
                
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    
                    @forelse($avaliacoes as $avaliacao)
                        <div class="border border-gray-200 rounded-xl p-5 hover:border-blue-300 hover:shadow-md transition-all bg-white relative">
                            
                            <h3 class="font-bold text-gray-900 text-lg uppercase leading-tight mb-2">
                                {{ $avaliacao->patient->name }}
                            </h3>
                            
                            <div class="flex items-center gap-1 text-xs text-gray-500 mb-4 bg-gray-50 inline-flex px-2 py-1 rounded-md border border-gray-100">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                {{ $avaliacao->patient->unit->city ?? 'N/A' }} • {{ $avaliacao->patient->agreement->name ?? 'Particular' }}
                            </div>

                            <p class="text-sm text-gray-700 mb-3">
                                {{ $avaliacao->professional->name }}
                            </p>

                            <div class="flex items-center gap-2 mb-6">
                                <span class="px-2 py-1 text-xs font-semibold rounded-md 
                                    {{ $avaliacao->current_session == 10 ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-green-50 text-green-600 border border-green-100' }}">
                                    Sessão {{ $avaliacao->current_session }} de 10
                                </span>
                                
                                <span class="px-2 py-1 text-xs font-semibold rounded-md 
                                    @if($avaliacao->status == 'Concluída') bg-blue-50 text-blue-700 border border-blue-200 
                                    @elseif($avaliacao->status == 'Cancelada') bg-red-50 text-red-700 border border-red-200 
                                    @else bg-gray-50 text-gray-700 border border-gray-200 @endif">
                                    {{ $avaliacao->status }}
                                </span>
                            </div>

                            <div class="border-t border-gray-100 pt-4">
                                <a href="{{ route('avaliacoes-neuro.edit', $avaliacao->id) }}" wire:navigate class="inline-flex items-center gap-2 text-sm font-semibold text-blue-600 hover:text-blue-800 transition-colors">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path></svg>
                                    Acessar Diário
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full py-12 text-center text-gray-500">
                            Nenhuma avaliação encontrada.
                        </div>
                    @endforelse

                </div>

                @if($avaliacoes->hasPages())
                    <div class="p-4 border-t border-gray-200 bg-gray-50">
                        {{ $avaliacoes->links() }}
                    </div>
                @endif

            </div>
        </div>
    </div>
</div>