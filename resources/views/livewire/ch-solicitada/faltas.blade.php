<div>
    <div class="mb-6 flex items-start gap-3">
        <a href="{{ route('ch-solicitada.index') }}" wire:navigate class="mt-1 p-2 -ml-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-full transition-colors" title="Voltar para Controle de CH">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Acompanhamento de Faltas</h1>
            <p class="text-sm text-gray-500 mt-1">Toda falta registrada no período, com ou sem CH solicitada cadastrada</p>
        </div>
    </div>

    <div class="max-w-full mx-auto py-6 sm:px-6 lg:px-8">

        {{-- ══════════════ Indicadores ══════════════ --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col justify-between relative overflow-hidden">
                <div class="absolute right-0 top-0 w-2 h-full bg-blue-500"></div>
                <h3 class="text-sm font-semibold text-gray-500 mb-2">Total de Faltas</h3>
                <p class="text-3xl font-bold text-gray-900 mb-3">{{ number_format($kpis['total'], 0, ',', '.') }}</p>
                <div class="text-sm text-blue-500 font-medium">No período filtrado</div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col justify-between relative overflow-hidden">
                <div class="absolute right-0 top-0 w-2 h-full bg-red-500"></div>
                <h3 class="text-sm font-semibold text-gray-500 mb-2">Sem CH Cadastrada</h3>
                <p class="text-3xl font-bold text-gray-900 mb-3">{{ number_format($kpis['sem_ch'], 0, ',', '.') }}</p>
                <div class="text-sm text-red-500 font-medium">Invisíveis em Controle de CH</div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col justify-between relative overflow-hidden">
                <div class="absolute right-0 top-0 w-2 h-full bg-orange-400"></div>
                <h3 class="text-sm font-semibold text-gray-500 mb-2">% Sem CH</h3>
                <p class="text-3xl font-bold text-gray-900 mb-3">{{ number_format($kpis['percentual_sem_ch'], 1, ',', '.') }}%</p>
                <div class="text-sm text-orange-500 font-medium">Do total de faltas</div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col justify-between relative overflow-hidden">
                <div class="absolute right-0 top-0 w-2 h-full bg-purple-500"></div>
                <h3 class="text-sm font-semibold text-gray-500 mb-2">Motivo Mais Frequente</h3>
                <p class="text-2xl font-bold text-gray-900 mb-3">{{ $kpis['motivo_mais_frequente'] ?? 'Sem dados' }}</p>
                <div class="text-sm text-purple-500 font-medium">No período filtrado</div>
            </div>
        </div>

        {{-- ══════════════ Filtros ══════════════ --}}
        <div class="bg-white shadow-sm sm:rounded-t-lg border border-gray-200 p-5 border-b-0">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-sm font-bold text-gray-800">Filtros</h3>
                @if ($unit_id !== '' || $search !== '' || $motivo !== '' || $somenteSemCh)
                    <button type="button" wire:click="limparFiltros" class="text-sm font-semibold text-red-500 hover:text-red-700 transition-colors">
                        Limpar filtros
                    </button>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Unidade</label>
                    <select wire:model.live="unit_id" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">Todas</option>
                        @foreach ($unidadesLista as $unidade)
                            <option value="{{ $unidade->id }}">{{ $unidade->city ?? $unidade->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Mês</label>
                    <select wire:model.live="month" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">Ano inteiro</option>
                        @foreach (['01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março', '04' => 'Abril', '05' => 'Maio', '06' => 'Junho', '07' => 'Julho', '08' => 'Agosto', '09' => 'Setembro', '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'] as $valor => $rotulo)
                            <option value="{{ (int) $valor }}">{{ $rotulo }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Ano</label>
                    <select wire:model.live="year" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        @foreach ($availableYears as $anoOpcao)
                            <option value="{{ $anoOpcao }}">{{ $anoOpcao }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Motivo</label>
                    <select wire:model.live="motivo" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">Todos</option>
                        @foreach ($motivoOptions as $valor => $rotulo)
                            <option value="{{ $valor }}">{{ $rotulo }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Paciente</label>
                    <input wire:model.live.debounce.400ms="search" type="text" placeholder="Buscar por nome do paciente" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
            </div>

            <div class="mt-4">
                <label class="inline-flex items-center gap-2 text-sm font-semibold text-gray-700 cursor-pointer">
                    <input type="checkbox" wire:model.live="somenteSemCh" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                    Mostrar somente faltas sem CH cadastrada
                </label>
            </div>
        </div>

        {{-- ══════════════ Listagem ══════════════ --}}
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-b-lg border border-gray-200">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase tracking-wider text-gray-500 font-semibold">
                            <th class="py-3 px-4">Data</th>
                            <th class="py-3 px-4">Paciente</th>
                            <th class="py-3 px-4">Terapia / Tipo</th>
                            <th class="py-3 px-4">Unidade</th>
                            <th class="py-3 px-4">Motivo</th>
                            <th class="py-3 px-4">Registrado por</th>
                            <th class="py-3 px-4">CH</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                        @forelse ($faltas as $falta)
                            <tr class="hover:bg-gray-50 transition-colors {{ ! $falta->temCh ? 'bg-red-50/40' : '' }}">
                                <td class="py-3 px-4 whitespace-nowrap font-medium text-gray-900">{{ $falta->date->format('d/m/Y') }}</td>
                                <td class="py-3 px-4 font-semibold text-gray-900">{{ $falta->patient?->name ?? 'Paciente removido' }}</td>
                                <td class="py-3 px-4">
                                    <div class="text-gray-800">{{ $falta->therapy?->name ?? '—' }}</div>
                                    <div class="text-xs text-gray-400">{{ $falta->serviceType?->name ?? 'Tipo não informado' }}</div>
                                </td>
                                <td class="py-3 px-4">{{ $falta->patient?->unit?->city ?? $falta->patient?->unit?->name ?? 'Não informada' }}</td>
                                <td class="py-3 px-4">
                                    <div>{{ $falta->motivoLabel() }}</div>
                                    @if ($falta->observacao)
                                        <div class="text-xs text-gray-400 truncate max-w-[220px]" title="{{ $falta->observacao }}">{{ $falta->observacao }}</div>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-gray-600">{{ $falta->registeredBy?->name ?? 'Não informado' }}</td>
                                <td class="py-3 px-4">
                                    @if ($falta->temCh)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-green-50 text-green-700 border border-green-100">
                                            Cadastrada
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-red-50 text-red-700 border border-red-100" title="Este paciente/terapia não tem CH solicitada cadastrada para este mês — não aparece em Controle de CH.">
                                            Sem CH
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-6 px-4 text-center text-gray-500">Nenhuma falta encontrada para os filtros selecionados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="py-3 px-4 border-t border-gray-200">
                {{ $faltas->links() }}
            </div>
        </div>
    </div>
</div>
