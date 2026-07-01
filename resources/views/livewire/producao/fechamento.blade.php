<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Fechamento de Produção</h1>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
        <div class="p-5 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-bold text-gray-800">Filtros de Apuração</h3>
            <button wire:click="limparFiltros" class="text-sm text-red-600 hover:text-red-800 font-medium transition-colors">
                Limpar Filtros
            </button>
        </div>
        <div class="p-5 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Mês</label>
                <select wire:model.live="mes" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="01">Janeiro</option>
                    <option value="02">Fevereiro</option>
                    <option value="03">Março</option>
                    <option value="04">Abril</option>
                    <option value="05">Maio</option>
                    <option value="06">Junho</option>
                    <option value="07">Julho</option>
                    <option value="08">Agosto</option>
                    <option value="09">Setembro</option>
                    <option value="10">Outubro</option>
                    <option value="11">Novembro</option>
                    <option value="12">Dezembro</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Ano</label>
                <select wire:model.live="ano" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="2024">2024</option>
                    <option value="2025">2025</option>
                    <option value="2026">2026</option>
                    <option value="2027">2027</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Profissional</label>
                <select wire:model.live="profissional_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="">Selecione uma opção</option>
                    @foreach($profissionaisLista as $prof)
                        <option value="{{ $prof->id }}">{{ $prof->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Terapia</label>
                <select wire:model.live="terapia_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="">Selecione uma opção</option>
                    @foreach($terapiasLista as $terapia)
                        <option value="{{ $terapia->id }}">{{ $terapia->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Unidade(s)</label>
                <select wire:model.live="unidade_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="">Selecione uma opção</option>
                    @foreach($unidadesLista as $unidade)
                        <option value="{{ $unidade->id }}">{{ $unidade->name ?? $unidade->city }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="bg-gray-50 p-4 border-t border-gray-100 flex justify-end rounded-b-xl">
            <button wire:click="$refresh" class="px-5 py-2 bg-blue-500 text-white font-semibold text-sm rounded-md hover:bg-blue-600 transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                Pesquisar Produção
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex items-center gap-4">
            <div class="p-3 bg-gray-50 rounded-lg">
                <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500">Total de Sessões Realizadas</p>
                <p class="text-3xl font-bold text-gray-900">{{ $totalSessoesGlobais }}</p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex items-center gap-4">
            <div class="p-3 bg-gray-50 rounded-lg">
                <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500">Total Geral da Clínica (Bruto)</p>
                <p class="text-3xl font-bold text-gray-900">R$ {{ number_format($totalValorGlobais, 2, ',', '.') }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-xs text-gray-800 font-bold">
                        <th class="py-3 px-4">Profissional</th>
                        <th class="py-3 px-4 text-center">Sessões Feitas</th>
                        <th class="py-3 px-4 text-center">Regra de Repasse</th>
                        <th class="py-3 px-4 text-right">Valor a Receber (Bruto)</th>
                        <th class="py-3 px-4 text-right">Ação</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($profissionais as $prof)
                        @php
                            // Busca os cálculos armazenados no cache daquele profissional
                            $resumo = $this->getResumoProducao($prof);
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="py-4 px-4 font-medium text-gray-900 uppercase text-xs">{{ $prof->name }}</td>
                            
                            <td class="py-4 px-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-blue-50 text-blue-600 border border-blue-100">
                                    {{ $resumo['sessoes'] }} sessões
                                </span>
                            </td>
                            
                            <td class="py-4 px-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">
                                    {{ $resumo['valor_regra'] }}
                                </span>
                            </td>
                            
                            <td class="py-4 px-4 text-right font-bold text-green-600 text-base">
                                R$ {{ number_format($resumo['valor_total'], 2, ',', '.') }}
                            </td>
                            
                            <td class="py-4 px-4 text-right">
                                <button wire:click="abrirExtrato({{ $prof->id }})" class="inline-flex items-center text-gray-500 hover:text-gray-700 font-medium text-xs gap-1 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    Ver Extrato
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-gray-500 text-sm">
                                Nenhuma produção encontrada para este período.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($profissionais->hasPages())
            <div class="py-3 px-4 border-t border-gray-100 bg-gray-50">
                {{ $profissionais->links() }}
            </div>
        @endif
    </div>

    @if($modalExtratoAberto)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                
                <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" wire:click="fecharExtrato"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full border border-gray-200">
                    
                    <div class="bg-white px-6 pt-5 pb-4 border-b border-gray-100">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-lg leading-6 font-bold text-gray-900" id="modal-title">
                                    Extrato: {{ mb_strtoupper($profissionalExtratoNome) }}
                                </h3>
                                <p class="text-xs text-gray-500 mt-1 font-semibold">
                                    Período Apurado: {{ str_pad($mes, 2, '0', STR_PAD_LEFT) }}/{{ $ano }}
                                </p>
                            </div>
                            <button wire:click="fecharExtrato" class="text-gray-400 hover:text-gray-600 transition-colors">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-6 py-4 max-h-[60vh] overflow-y-auto">
                        <table class="w-full text-left text-sm border-collapse">
                            <thead>
                                <tr class="border-b border-gray-200 text-xs text-gray-600 font-bold uppercase">
                                    <th class="py-2 px-2">Data do Atendimento</th>
                                    <th class="py-2 px-2">Paciente</th>
                                    <th class="py-2 px-2">Terapia</th>
                                    <th class="py-2 px-2 text-center">Sessões (Qtd)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 text-gray-800">
                                @forelse($extratoAtendimentos as $atendimento)
                                    <tr class="hover:bg-white transition-colors">
                                        <td class="py-3 px-2 text-xs">
                                            {{ \Carbon\Carbon::parse($atendimento->appointment_date)->format('d/m/Y') }}
                                        </td>
                                        <td class="py-3 px-2 font-medium uppercase text-xs">
                                            {{ $atendimento->patient->name ?? '-' }}
                                        </td>
                                        <td class="py-3 px-2 text-xs">
                                            {{ $atendimento->therapy->name ?? '-' }}
                                        </td>
                                        <td class="py-3 px-2 text-center font-bold">
                                            {{ $atendimento->session_number }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-6 text-center text-gray-500 text-xs">
                                            Nenhum detalhe encontrado para este período.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    @endif

</div>