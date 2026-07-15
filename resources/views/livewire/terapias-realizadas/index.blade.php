<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Terapias Realizadas</h1>
        <p class="text-sm text-gray-500 mt-1">Registro de todas as consultas da clínica</p>
    </div>

    @hasanyrole('admin|manager|administrative')
        <div class="max-w-full mx-auto sm:px-6 lg:px-8 mt-6 mb-4">
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('terapias-realizadas.create') }}" class="px-4 py-2 bg-blue-500 text-white text-sm font-semibold rounded-md hover:bg-blue-600 transition-colors shadow-sm">
                    Registrar Atendimento
                </a>

                @hasanyrole('admin|manager')
                <button type="button" wire:click.prevent="exportPdf" class="px-4 py-2 bg-red-500 text-white text-sm font-semibold rounded-md hover:bg-red-600 transition-colors shadow-sm flex items-center gap-2">
                    <svg wire:loading.remove wire:target="exportPdf" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <svg wire:loading wire:target="exportPdf" class="animate-spin w-5 h-5 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="exportPdf">Exportar para PDF</span>
                    <span wire:loading wire:target="exportPdf">Gerando...</span>
                </button>

                <button type="button" wire:click.prevent="exportExcel" wire:loading.attr="disabled" class="px-4 py-2 bg-green-500 text-white text-sm font-semibold rounded-md hover:bg-green-600 transition-colors shadow-sm flex items-center gap-2 disabled:opacity-50">
                    <svg wire:loading.remove wire:target="exportExcel" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                    
                    <svg wire:loading wire:target="exportExcel" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>

                    <span wire:loading.remove wire:target="exportExcel">Exportar para Excel</span>
                    <span wire:loading wire:target="exportExcel">Gerando Planilha...</span>
                </button>

                <button type="button" wire:click="$set('showImportModal', true)" class="px-4 py-2 bg-yellow-500 text-white text-sm font-semibold rounded-md hover:bg-yellow-600 transition-colors shadow-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    Importar CSV Unimed
                </button>
                @endhasanyrole
            </div>
        </div>
    @endhasanyrole

    <div class="mb-6 flex flex-wrap gap-4">
        <div class="bg-white border border-gray-200 shadow-sm rounded-xl px-5 py-3 flex items-center gap-4 min-w-[200px] flex-1">
            <div class="p-2.5 bg-indigo-50 text-indigo-600 rounded-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total - Atendimentos</p>
                <p class="text-xl font-bold text-gray-900 leading-tight">{{ $totalConsultas }}</p>
            </div>
        </div>

        <div class="bg-white border border-gray-200 shadow-sm rounded-xl px-5 py-3 flex items-center gap-4 min-w-[200px] flex-1">
            <div class="p-2.5 bg-emerald-50 text-emerald-600 rounded-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total - Sessões</p>
                <p class="text-xl font-bold text-gray-900 leading-tight">{{ $totalSessoes ?? 0 }}</p>
            </div>
        </div>
    </div>

    <div class="max-w-full mx-auto py-6 sm:px-6 lg:px-8">
        
        <div class="bg-white shadow-sm sm:rounded-t-lg border border-gray-200 p-4 border-b-0">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-sm font-bold text-gray-800">Filtros</h3>
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
                    <select wire:model="professional_id" 
                            class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ !auth()->user()->hasAnyRole(['admin', 'manager', 'administrative']) ? 'bg-gray-100 cursor-not-allowed opacity-75' : '' }}" 
                            {{ !auth()->user()->hasAnyRole(['admin', 'manager', 'administrative']) ? 'disabled' : '' }}>
                        
                        @hasanyrole('admin|manager|administrative')
                            <option value="">Todos</option>
                        @endhasanyrole
                        
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
                            @if($selectedColumns['qtd_sessoes']) <th class="py-3 px-4">Qtd de Sessões</th> @endif
                            @if($selectedColumns['profissional']) <th class="py-3 px-4">Profissional</th> @endif
                            @if($selectedColumns['registrado_em']) <th class="py-3 px-4">Registrado em</th> @endif
                            @if($selectedColumns['atualizado_em']) <th class="py-3 px-4">Atualizado em</th> @endif
                            
                            @hasanyrole('admin|manager|administrative')
                                <th class="py-3 px-4 text-right">Ações</th>
                            @endhasanyrole
                        </tr>   
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-800">
                        @forelse ($appointments as $appointment)
                            <tr class="hover:bg-gray-50 transition-colors whitespace-nowrap">
                                @if($selectedColumns['nome']) <td class="py-4 px-4 font-medium uppercase text-xs">{{ $appointment->patient?->name }}</td> @endif
                                @if($selectedColumns['data']) <td class="py-4 px-4">{{ $appointment->appointment_date ? \Carbon\Carbon::parse($appointment->appointment_date)->format('d/m/Y') : '-' }}</td> @endif
                                @if($selectedColumns['guia']) <td class="py-4 px-4">{{ $appointment->guide ?? '-' }}</td> @endif
                                @if($selectedColumns['terapia']) <td class="py-4 px-4 uppercase text-xs">{{ $appointment->therapy?->name }}</td> @endif
                                @if($selectedColumns['tipo_atendimento']) <td class="py-4 px-4 uppercase text-xs">{{ $appointment->serviceType?->name ?? '-' }}</td> @endif
                                @if($selectedColumns['check_in']) <td class="py-4 px-4">{{ $appointment->check_in ? \Carbon\Carbon::parse($appointment->check_in)->format('H:i:s') : '-' }}</td> @endif
                                @if($selectedColumns['check_out']) <td class="py-4 px-4">{{ $appointment->check_out ? \Carbon\Carbon::parse($appointment->check_out)->format('H:i:s') : '-' }}</td> @endif
                                @if($selectedColumns['qtd_sessoes']) <td class="py-4 px-4">{{ $appointment->session_number ?? 1 }}</td> @endif
                                @if($selectedColumns['profissional']) <td class="py-4 px-4 uppercase text-xs">{{ $appointment->professional?->name ?? '-' }}</td> @endif
                                @if($selectedColumns['registrado_em']) <td class="py-4 px-4 text-xs">{{ $appointment->created_at ? $appointment->created_at->format('d/m/Y H:i') : '-' }}</td> @endif
                                @if($selectedColumns['atualizado_em']) <td class="py-4 px-4 text-xs">{{ $appointment->updated_at ? $appointment->updated_at->format('d/m/Y H:i') : '-' }}</td> @endif
                                
                                @hasanyrole('admin|manager|administrative')
                                    <td class="py-4 px-4 text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            <a href="{{ route('terapias-realizadas.edit', $appointment->id) }}" wire:navigate class="text-blue-600 hover:text-blue-800 font-semibold text-xs transition-colors">
                                                Editar
                                            </a>
                                            <button 
                                                type="button" 
                                                wire:click="deleteAppointment({{ $appointment->id }})" 
                                                wire:confirm="Tem certeza que deseja excluir esta consulta? Essa ação não pode ser desfeita."
                                                class="text-red-500 hover:text-red-700 font-semibold text-xs transition-colors"
                                            >
                                                Excluir
                                            </button>
                                        </div>
                                    </td>
                                @endhasanyrole
                            </tr>
                        @empty
                            <tr>
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

    <div x-data="{ show: @entangle('showImportModal') }" x-show="show" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" style="display: none;">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="show = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                
                <form wire:submit.prevent="processImport">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Importar CSV Unimed</h3>
                                
                                <div class="mt-4 space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Unidade do Relatório <span class="text-red-500">*</span></label>
                                        <select wire:model="unidade_relatorio" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md" required>
                                            <option value="">Selecione...</option>
                                            <option value="Mossoró">Mossoró</option>
                                            <option value="Natal">Natal</option>
                                            <option value="João Câmara">João Câmara</option>
                                        </select>
                                        @error('unidade_relatorio') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Arquivo CSV <span class="text-red-500">*</span></label>
                                        <input type="file" wire:model="arquivo_csv" accept=".csv" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" required>
                                        @error('arquivo_csv') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>

                                    @if (session()->has('success'))
                                        <div class="p-3 bg-green-50 text-green-700 text-sm rounded border border-green-200">
                                            {{ session('success') }}
                                        </div>
                                    @endif
                                    @if (session()->has('warning'))
                                        <div class="p-3 bg-yellow-50 text-yellow-700 text-sm rounded border border-yellow-200 font-semibold">
                                            {{ session('warning') }}
                                        </div>
                                    @endif

                                    @if (!empty($importMessages))
                                        <div class="max-h-40 overflow-y-auto bg-red-50 p-3 rounded border border-red-200 text-xs text-red-700 mt-2 space-y-1">
                                            <p class="font-bold mb-2">Exibindo os primeiros erros:</p>
                                            @foreach(array_slice($importMessages, 0, 10) as $msg)
                                                <p>{!! $msg !!}</p>
                                            @endforeach
                                            @if(count($importMessages) > 10)
                                                <p class="mt-2 italic">... e mais {{ count($importMessages) - 10 }} erros ocultados.</p>
                                            @endif
                                        </div>
                                    @endif

                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-200">
                        <button type="submit" wire:loading.attr="disabled" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
                            <span wire:loading.remove wire:target="processImport">Processar Importação</span>
                            <span wire:loading wire:target="processImport">Processando...</span>
                        </button>
                        <button type="button" @click="show = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Fechar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>