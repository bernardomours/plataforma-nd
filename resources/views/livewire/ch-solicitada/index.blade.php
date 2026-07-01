<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">CH - Solicitada</h1>
        <p class="text-sm text-gray-500 mt-1">Registro de todas as cargas horárias solicitadas, liberadas e planejadas</p>
    </div>

    <div class="max-w-full mx-auto py-6 sm:px-6 lg:px-8">
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col justify-between relative overflow-hidden">
                <div class="absolute right-0 top-0 w-2 h-full bg-orange-400"></div>
                <h3 class="text-sm font-semibold text-gray-500 mb-2">Horas Solicitadas</h3>
                <p class="text-3xl font-bold text-gray-900 mb-3">{{ number_format($totalHorasSolicitadas, 0, ',', '.') }}</p>
                <div class="flex items-center text-sm text-orange-500 font-medium gap-1">
                    <span>Total das horas solicitadas</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col justify-between relative overflow-hidden">
                <div class="absolute right-0 top-0 w-2 h-full bg-green-500"></div>
                <h3 class="text-sm font-semibold text-gray-500 mb-2">Horas Autorizadas</h3>
                <p class="text-3xl font-bold text-gray-900 mb-3">{{ number_format($totalHorasLiberadas, 0, ',', '.') }}</p>
                <div class="flex items-center text-sm text-green-600 font-medium gap-1">
                    <span>Total das horas autorizadas</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col justify-between relative overflow-hidden">
                <div class="absolute right-0 top-0 w-2 h-full bg-blue-500"></div>
                <h3 class="text-sm font-semibold text-gray-500 mb-2">Horas Planejadas</h3>
                <p class="text-3xl font-bold text-gray-900 mb-3">{{ number_format($totalHorasPlanejadas, 0, ',', '.') }}</p>
                <div class="flex items-center text-sm text-blue-500 font-medium gap-1">
                    <span>Total das horas planejadas</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
            </div>
        </div>

        <div class="bg-white shadow-sm sm:rounded-t-lg border border-gray-200 p-5 border-b-0">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-sm font-bold text-gray-800">Filtros</h3>
                <button type="button" wire:click="clearFilters" class="text-sm font-semibold text-red-500 hover:text-red-700 transition-colors">
                    Limpar filtros
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Unidade</label>
                    <select wire:model.live="unit_id" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">Todos</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->city ?? $unit->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Mês</label>
                    <select wire:model.live="month" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">Selecione uma opção</option>
                        <option value="1">Janeiro</option>
                        <option value="2">Fevereiro</option>
                        <option value="3">Março</option>
                        <option value="4">Abril</option>
                        <option value="5">Maio</option>
                        <option value="6">Junho</option>
                        <option value="7">Julho</option>
                        <option value="8">Agosto</option>
                        <option value="9">Setembro</option>
                        <option value="10">Outubro</option>
                        <option value="11">Novembro</option>
                        <option value="12">Dezembro</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Ano</label>
                    <select wire:model.live="year" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">Selecione uma opção</option>
                        @foreach($availableYears as $ano)
                            <option value="{{ $ano }}">{{ $ano }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-b-lg border border-gray-200">
            <div class="p-4 border-b border-gray-200 bg-gray-50 flex justify-end items-center gap-4">
                <div class="relative w-full sm:w-64">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Pesquisar..." class="block w-full pl-10 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse">
                    <thead>
                        <tr class="bg-white border-b border-gray-200 text-xs text-gray-600 font-bold whitespace-nowrap uppercase tracking-wide">
                            <th class="py-4 px-4 text-center w-8"><input type="checkbox" disabled class="rounded border-gray-300 text-blue-600 shadow-sm opacity-50"></th>
                            <th class="py-4 px-4">Nome</th>
                            <th class="py-4 px-4">Terapia</th>
                            <th class="py-4 px-4">Tipo de Atendimento</th>
                            <th class="py-4 px-4">Mês/Ano</th>
                            <th class="py-4 px-4">Requisição</th>
                            <th class="py-4 px-4 text-center">Horas Solicitadas</th>
                            <th class="py-4 px-4 text-center">Horas Liberadas</th>
                            <th class="py-4 px-4 text-center">Horas Planejadas</th>
                        </tr>   
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-800">
                        @forelse ($registros as $registro)
                            <tr wire:key="registro-{{ $registro->id }}" class="hover:bg-gray-50 transition-colors whitespace-nowrap">
                                <td class="py-4 px-4 text-center"><input type="checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm"></td>
                                <td class="py-4 px-4 font-medium text-xs uppercase">{{ $registro->patient->name ?? '-' }}</td>
                                <td class="py-4 px-4 text-xs uppercase">{{ $registro->therapy->name ?? '-' }}</td>
                                <td class="py-4 px-4 text-xs">{{ $registro->serviceType->name ?? 'Clínica' }}</td>
                                <td class="py-4 px-4 text-xs">
                                    {{ $registro->month_year ? \Carbon\Carbon::parse($registro->month_year)->translatedFormat('F \d\e Y') : '-' }}
                                </td>
                                <td class="py-4 px-4 text-xs">{{ $registro->requisition_number ?? '-' }}</td>
                                <td class="py-4 px-4 text-center font-semibold">{{ $registro->requested_hours ?? 0 }}</td>
                                <td class="py-4 px-4 text-center font-semibold">{{ $registro->approved_hours ?? 0 }}</td>
                                <td class="py-4 px-4 text-center font-semibold">{{ $registro->planned_hours ?? 0 }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-8 px-4 text-center text-gray-500 flex flex-col items-center justify-center">
                                    <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                    Nenhum registro de carga horária encontrado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="py-3 px-4 border-t border-gray-200">
                {{ $registros->links() }}
            </div>
        </div>
    </div>
</div>