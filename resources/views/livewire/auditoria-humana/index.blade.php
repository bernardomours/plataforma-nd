<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Auditoria - Humana</h1>
        <p class="text-sm text-gray-500 mt-1">Cruze os dados do Relatório da Humana com as terapias realizadas no sistema.</p>
    </div>

    @if(!$processado)
        <!-- TELA DE UPLOAD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 max-w-3xl mx-auto mt-8">
            <form wire:submit="processar" class="space-y-6">
                
                <!-- Nova estrutura com 3 colunas -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Mês de Competência</label>
                        <select wire:model="mes" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}">{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}</option>
                            @endfor
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Ano de Competência</label>
                        <select wire:model="ano" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="2026">2026</option>
                            <option value="2025">2025</option>
                            <option value="2024">2024</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Unidade do Relatório <span class="text-red-500">*</span></label>
                        <select wire:model="unidade_relatorio" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Selecione a Unidade...</option>
                            @foreach($unidades as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->city ?? $unit->name }}</option>
                            @endforeach
                        </select>
                        @error('unidade_relatorio') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Arquivo CSV Convertido <span class="text-red-500">*</span></label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:bg-gray-50 transition-colors">
                        <div class="space-y-1 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-sm text-gray-600 justify-center">
                                <label for="file-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                    <span>Selecione um arquivo</span>
                                    <input id="file-upload" type="file" wire:model="arquivo_csv" accept=".csv" class="sr-only">
                                </label>
                            </div>
                            <p class="text-xs text-gray-500">Apenas arquivos .CSV</p>
                        </div>
                    </div>
                    @error('arquivo_csv') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    
                    <div wire:loading wire:target="arquivo_csv" class="text-sm text-blue-600 mt-2 font-semibold">
                        Carregando arquivo...
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-100 flex justify-end">
                    <button type="submit" wire:loading.attr="disabled" class="px-6 py-2 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2 shadow-sm disabled:opacity-50">
                        <span wire:loading.remove wire:target="processar">Processar Auditoria</span>
                        <span wire:loading wire:target="processar">Analisando Dados...</span>
                    </button>
                </div>
            </form>
        </div>

    @else
        <!-- TELA DE RESULTADOS -->
        <div class="flex gap-3 mb-6">
                <!-- Botão de Exportar PDF -->
                <button wire:click="exportarPDF" wire:loading.attr="disabled" class="px-4 py-2 bg-red-600 text-white font-bold rounded-lg hover:bg-emerald-700 transition-colors text-sm border border-emerald-700 flex items-center gap-2 shadow-sm">
                    <svg wire:loading.remove wire:target="exportarPDF" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span wire:loading wire:target="exportarPDF" class="w-4 h-4 rounded-full border-2 border-white border-t-transparent animate-spin"></span>
                    <span>Gerar PDF</span>
                </button>

                <!-- Botão de Nova Auditoria -->
                <button wire:click="novaAuditoria" class="px-4 py-2 bg-emerald-600 text-white font-bold rounded-lg hover:bg-gray-200 transition-colors text-sm border border-gray-300">
                    Nova Auditoria
                </button>
            </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            
            <div class="overflow-x-auto overflow-y-auto max-h-[70vh]">
                
                <table class="w-full text-left text-sm border-separate border-spacing-0">
                    
                    <thead class="sticky top-0 z-20 shadow-sm">
                        <tr class="text-xs text-gray-800 font-bold uppercase tracking-wider">
                            <th class="py-4 px-4 bg-gray-50 border-b border-gray-200">Paciente</th>
                            <th class="py-4 px-4 bg-gray-50 border-b border-gray-200">Terapia</th>
                            
                            <th class="py-4 px-4 text-center bg-blue-50 text-blue-900 border-b border-blue-200 border-x border-blue-100">Qtd. Sistema</th>
                            <th class="py-4 px-4 text-center bg-indigo-50 text-indigo-900 border-b border-indigo-200 border-r border-indigo-100">Qtd. Humana</th>
                            
                            <th class="py-4 px-4 text-right bg-gray-50 border-b border-gray-200">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($resultados as $res)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="py-4 px-4 font-bold text-gray-900">{{ $res['paciente'] }}</td>
                                <td class="py-4 px-4 text-gray-600 font-medium">{{ $res['terapia'] }}</td>
                                
                                <td class="py-4 px-4 text-center font-bold text-gray-900 bg-blue-50/30 border-x border-gray-100">{{ $res['qtd_sistema'] }}</td>
                                <td class="py-4 px-4 text-center font-bold text-gray-900 bg-indigo-50/30 border-r border-gray-100">{{ $res['qtd_humana'] }}</td>
                                
                                <td class="py-4 px-4 text-right">
                                    @if($res['cor'] === 'green')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold bg-green-100 text-green-800">
                                            {{ $res['status'] }}
                                        </span>
                                    @elseif($res['cor'] === 'red')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold bg-red-100 text-red-800">
                                            {{ $res['status'] }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold bg-yellow-100 text-yellow-800">
                                            {{ $res['status'] }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-gray-500">Nenhum dado encontrado ou planilha vazia.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>