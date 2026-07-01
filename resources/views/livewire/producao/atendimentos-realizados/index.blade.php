<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Auditoria de Atendimentos</h1>
        <p class="text-sm text-gray-500 mt-1">Registro de todas as consultas realizadas (Visualização do RH)</p>
    </div>

    <!-- Cards de Resumo -->
    <div class="mb-6 flex flex-wrap gap-4 mt-6">
        <div class="bg-white border border-gray-200 shadow-sm rounded-xl px-5 py-3 flex items-center gap-4 min-w-[200px] flex-1">
            <div class="p-2.5 bg-indigo-50 text-indigo-600 rounded-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Consultas</p>
                <p class="text-xl font-bold text-gray-900 leading-tight">{{ $totalConsultas }}</p>
            </div>
        </div>

        <div class="bg-white border border-gray-200 shadow-sm rounded-xl px-5 py-3 flex items-center gap-4 min-w-[200px] flex-1">
            <div class="p-2.5 bg-emerald-50 text-emerald-600 rounded-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Sessões</p>
                <p class="text-xl font-bold text-gray-900 leading-tight">{{ $totalSessoes ?? 0 }}</p>
            </div>
        </div>
    </div>

    <div class="max-w-full mx-auto py-6">
        
        <!-- Filtros -->
        <div class="bg-white shadow-sm sm:rounded-t-lg border border-gray-200 p-4 border-b-0">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-sm font-bold text-gray-800">Filtros de Auditoria</h3>
                <button type="button" wire:click="clearFilters" class="text-sm font-semibold text-red-500 hover:text-red-700 transition-colors">
                    Limpar filtros
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Paciente</label>
                    <select wire:model="patient_id" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">Todos</option>
                        @foreach($patients as $patient)
                            <option value="{{ $patient->id }}">{{ $patient->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Profissional</label>
                    <select wire:model="professional_id" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">Todos</option>
                        @foreach($professionals as $professional)
                            <option value="{{ $professional->id }}">{{ $professional->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Convênio</label>
                    <select wire:model="agreement_id" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">Todos</option>
                        @foreach($agreements as $agreement)
                            <option value="{{ $agreement->id }}">{{ $agreement->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Terapia</label>
                    <select wire:model="therapy_id" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">Todos</option>
                        @foreach($therapies as $therapy)
                            <option value="{{ $therapy->id }}">{{ $therapy->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Tipo de Atendimento</label>
                    <select wire:model="service_type_id" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">Todos</option>
                        @foreach($serviceTypes as $serviceType)
                            <option value="{{ $serviceType->id }}">{{ $serviceType->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Unidade</label>
                    <select wire:model="unit_id" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">Todos</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->city ?? $unit->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Número da Guia</label>
                    <input type="text" wire:model="guide" placeholder="Digite para pesquisar..." class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Data Início</label>
                    <input type="date" wire:model="start_date" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Data Fim</label>
                    <input type="date" wire:model="end_date" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
            </div>

            <button type="button" wire:click="applyFilters" class="px-4 py-2 bg-blue-400 text-white text-sm font-semibold rounded-md hover:bg-blue-500 transition-colors">
                Aplicar filtros
            </button>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-b-lg border border-gray-200">
            
            <div class="p-4 border-b border-gray-200 bg-gray-50 flex justify-between sm:justify-end items-center gap-4">
                
                <input wire:model.live="search" type="text" placeholder="Pesquisar paciente..." class="block w-full sm:w-64 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" @click.away="open = false" class="p-2 bg-white border border-gray-300 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-50 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path></svg>
                    </button>

                    <div x-show="open" style="display: none;" class="absolute right-0 mt-2 w-56 bg-white rounded-md shadow-lg border border-gray-200 z-50">
                        <div class="p-3 border-b border-gray-100 flex justify-between items-center">
                            <span class="font-bold text-sm text-gray-800">Colunas</span>
                            <button wire:click="resetColumns" class="text-xs font-semibold text-red-500 hover:text-red-700">Redefinir</button>
                        </div>
                        
                        <div class="p-3 space-y-2 max-h-64 overflow-y-auto text-sm text-gray-700">
                            <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" wire:model.live="selectedColumns.nome" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"> Nome</label>
                            <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" wire:model.live="selectedColumns.data" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"> Data</label>
                            <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" wire:model.live="selectedColumns.guia" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"> Guia</label>
                            <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" wire:model.live="selectedColumns.terapia" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"> Terapia</label>
                            <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" wire:model.live="selectedColumns.tipo_atendimento" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"> Tipo de Atendimento</label>
                            <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" wire:model.live="selectedColumns.check_in" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"> Check-in</label>
                            <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" wire:model.live="selectedColumns.check_out" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"> Check-out</label>
                            <!-- NOVA COLUNA NO MENU -->
                            <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" wire:model.live="selectedColumns.duracao" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"> Duração</label>
                            <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" wire:model.live="selectedColumns.qtd_sessoes" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"> Qtd de Sessões</label>
                            <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" wire:model.live="selectedColumns.profissional" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"> Profissional</label>
                            <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" wire:model.live="selectedColumns.registrado_em" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"> Registrado em</label>
                            <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" wire:model.live="selectedColumns.atualizado_em" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"> Atualizado em</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse">
                    <thead>
                        <tr class="bg-white border-b border-gray-200 text-xs text-gray-600 font-bold whitespace-nowrap">
                            @if($selectedColumns['nome']) <th class="py-3 px-4">Nome</th> @endif
                            @if($selectedColumns['data']) <th class="py-3 px-4">Data</th> @endif
                            @if($selectedColumns['guia']) <th class="py-3 px-4">Guia</th> @endif
                            @if($selectedColumns['terapia']) <th class="py-3 px-4">Terapia</th> @endif
                            @if($selectedColumns['tipo_atendimento']) <th class="py-3 px-4">Tipo de Atendimento</th> @endif
                            @if($selectedColumns['check_in']) <th class="py-3 px-4">Check-in</th> @endif
                            @if($selectedColumns['check_out']) <th class="py-3 px-4">Check-out</th> @endif
                            @if($selectedColumns['duracao'] ?? true) <th class="py-3 px-4 text-center bg-blue-50 text-blue-800">Duração</th> @endif
                            @if($selectedColumns['qtd_sessoes']) <th class="py-3 px-4">Qtd de Sessões</th> @endif
                            @if($selectedColumns['profissional']) <th class="py-3 px-4">Profissional</th> @endif
                        </tr>   
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-800">
                        @forelse ($appointments as $appointment)
                            
                            @php
                                $duracao = '--';
                                $alertaVermelho = false;

                                // Regra 1: Se não tiver check-out, acende o alerta vermelho
                                if (!$appointment->check_out) {
                                    $alertaVermelho = true;
                                }

                                if ($appointment->check_in && $appointment->check_out) {
                                    $inicio = \Carbon\Carbon::parse($appointment->check_in);
                                    $fim = \Carbon\Carbon::parse($appointment->check_out);
                                    
                                    // Regra 2: Verifica se o total de minutos é menor que 30
                                    if ($inicio->diffInMinutes($fim) < 30) {
                                        $alertaVermelho = true;
                                    }
                                    
                                    $duracao = $inicio->diff($fim)->format('%H:%I');
                                }
                            @endphp

                            <tr class="{{ $alertaVermelho ? 'bg-red-50 hover:bg-red-100' : 'hover:bg-gray-50' }} transition-colors whitespace-nowrap">
                                @if($selectedColumns['nome']) <td class="py-4 px-4 font-medium uppercase text-xs">{{ $appointment->patient?->name }}</td> @endif
                                @if($selectedColumns['data']) <td class="py-4 px-4">{{ $appointment->appointment_date ? \Carbon\Carbon::parse($appointment->appointment_date)->format('d/m/Y') : '-' }}</td> @endif
                                @if($selectedColumns['guia']) <td class="py-4 px-4">{{ $appointment->guide ?? '-' }}</td> @endif
                                @if($selectedColumns['terapia']) <td class="py-4 px-4 uppercase text-xs">{{ $appointment->therapy?->name }}</td> @endif
                                @if($selectedColumns['tipo_atendimento']) <td class="py-4 px-4 uppercase text-xs">{{ $appointment->serviceType?->name ?? '-' }}</td> @endif
                                @if($selectedColumns['check_in']) <td class="py-4 px-4">{{ $appointment->check_in ? \Carbon\Carbon::parse($appointment->check_in)->format('H:i:s') : '-' }}</td> @endif
                                @if($selectedColumns['check_out']) <td class="py-4 px-4">{{ $appointment->check_out ? \Carbon\Carbon::parse($appointment->check_out)->format('H:i:s') : '-' }}</td> @endif
                                
                                <!-- EXIBIÇÃO DA NOVA COLUNA (Fica vermelha se houver alerta) -->
                                @if($selectedColumns['duracao'] ?? true) 
                                    <td class="py-4 px-4 text-center font-bold {{ $alertaVermelho ? 'text-red-700 bg-red-200/50' : 'text-blue-700 bg-blue-50/30' }}">
                                        {{ $duracao }}
                                    </td> 
                                @endif
                                
                                @if($selectedColumns['qtd_sessoes']) <td class="py-4 px-4">{{ $appointment->session_number ?? 1 }}</td> @endif
                                @if($selectedColumns['profissional']) <td class="py-4 px-4 uppercase text-xs">{{ $appointment->professional?->name ?? '-' }}</td> @endif
                            </tr>
                        @empty
                            <tr>
                                <!-- Ajustado o colspan de 11 para 12 por conta da nova coluna -->
                                <td colspan="12" class="py-6 px-4 text-center text-gray-500">Nenhuma terapia encontrada com os filtros atuais.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="py-3 px-4 border-t border-gray-200">
                {{ $appointments->links() }}
            </div>
        </div>
    </div>
</div>