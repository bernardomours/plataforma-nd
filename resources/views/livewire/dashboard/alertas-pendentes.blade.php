<div>
    @if($visitasAtrasadas->count() > 0)
        <div class="bg-white shadow-sm sm:rounded-xl border border-red-200 overflow-hidden mb-6">
            
            <!-- Cabeçalho do Alerta -->
            <div class="bg-red-50 p-4 border-b border-red-100 flex items-center justify-between">
                <h3 class="font-bold text-red-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-red-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    Atenção: Coordenações e Supervisões pendentes há mais de 10 dias ({{ $visitasAtrasadas->count() }})
                </h3>
                <span class="text-xs text-red-600 font-medium bg-red-100 px-2 py-1 rounded-md">Clique na linha para gerenciar</span>
            </div>
            
            <!-- Lista -->
            <div class="overflow-x-auto max-h-80 overflow-y-auto">
                <table class="w-full text-left text-sm border-collapse">
                    <thead>
                        <tr class="bg-white border-b border-gray-100 text-xs text-gray-500 font-bold uppercase tracking-wider sticky top-0 shadow-sm">
                            <th class="py-3 px-4">Paciente</th>
                            <th class="py-3 px-4">Terapia / Tipo</th>
                            <th class="py-3 px-4">Profissional</th>
                            <th class="py-3 px-4 text-center">Tempo de Espera</th>
                            <th class="py-3 px-4 text-right">Disponível Desde</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($visitasAtrasadas as $visita)
                            @php
                                $dias = (int) \Carbon\Carbon::parse($visita->created_at)->diffInDays(now());
                                $corAlerta = $dias > 20 ? 'bg-red-100 text-red-800 border-red-200' : 'bg-orange-100 text-orange-800 border-orange-200';
                            @endphp
                            
                            <!-- Adicionado wire:click e cursor-pointer -->
                            <tr wire:click="abrirOpcoes({{ $visita->id }})" class="hover:bg-red-50 transition-colors cursor-pointer" title="Clique para gerenciar">
                                <td class="py-3 px-4 font-bold text-gray-800 text-xs uppercase">{{ $visita->patient->name ?? '-' }}</td>
                                <td class="py-3 px-4 text-xs">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 mr-2 border border-blue-100">
                                        {{ $visita->therapy->name ?? '-' }}
                                    </span>
                                    <span class="text-gray-600 font-medium">{{ $visita->type instanceof \App\Enums\VisitType ? $visita->type->getLabel() : $visita->type }}</span>
                                </td>
                                <td class="py-3 px-4 text-xs uppercase text-gray-600">{{ $visita->professional->name ?? '-' }}</td>
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $corAlerta }}">
                                        {{ $dias }} dias
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right text-xs font-semibold text-gray-500">
                                    {{ $visita->created_at->format('d/m/Y') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- MODAL DE OPÇÕES -->
    @if($modalAberto)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                
                <!-- Fundo escuro -->
                <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" wire:click="fecharModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <!-- Caixa do Modal -->
                <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-200">
                    
                    <div class="bg-white px-6 pt-5 pb-4 border-b border-gray-100">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-blue-50 text-blue-600 rounded-lg">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-lg leading-6 font-bold text-gray-900" id="modal-title">
                                    Gerenciar Pendência
                                </h3>
                                <p class="text-sm text-gray-500 mt-1">
                                    Escolha uma ação para esta atividade atrasada.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Resumo da Visita -->
                    <div class="px-6 py-4 bg-gray-50 text-sm text-gray-700 space-y-2">
                        <p><span class="font-bold text-gray-900">Paciente:</span> {{ $visitaSelecionadaInfo['paciente'] ?? '' }}</p>
                        <p><span class="font-bold text-gray-900">Atividade:</span> {{ $visitaSelecionadaInfo['tipo'] ?? '' }}</p>
                        <p><span class="font-bold text-gray-900">Disponível Desde:</span> {{ $visitaSelecionadaInfo['data'] ?? '' }}</p>
                    </div>

                    <!-- Botões de Ação -->
                    <div class="bg-white px-6 py-4 flex flex-col sm:flex-row-reverse gap-3 border-t border-gray-200">
                        
                        @if($visitaSelecionadaId)
                            <a href="{{ url('/acompanhamentos?edit=' . $visitaSelecionadaId) }}" class="w-full inline-flex justify-center items-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:w-auto sm:text-sm transition-colors">
                                Ajustar / Preencher
                            </a>
                        @endif

                        <button type="button" wire:click="excluirVisita" wire:confirm="Tem certeza que deseja excluir este registro pendente? Essa ação não pode ser desfeita." class="w-full inline-flex justify-center items-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-red-50 text-base font-medium text-red-700 hover:bg-red-100 hover:border-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:w-auto sm:text-sm transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            Excluir (Erro)
                        </button>

                        <button type="button" wire:click="fecharModal" class="w-full inline-flex justify-center items-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:w-auto sm:text-sm">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>