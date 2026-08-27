<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8 space-y-6">

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="h-3 bg-blue-600"></div>
        
        <div class="p-6 sm:p-8">
            <div class="flex flex-col sm:flex-row items-start justify-between gap-6">
                
                <div class="flex items-start gap-5">
                    <div class="h-16 w-16 sm:h-20 sm:w-20 rounded-full bg-blue-50 border border-blue-100 flex-shrink-0 flex items-center justify-center text-blue-600 font-bold text-xl sm:text-2xl uppercase tracking-wider">
                        {{ substr($patient->name, 0, 2) }}
                    </div>
                    
                    <div class="pt-1">
                        <div class="flex flex-wrap items-center gap-3 mb-1.5">
                            <h1 class="text-xl sm:text-2xl font-bold text-gray-900 uppercase tracking-tight">{{ $patient->name }}</h1>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100 shadow-sm">
                                {{ $patient->agreement->name ?? 'Sem Convênio' }}
                            </span>
                        </div>
                        
                        <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-gray-500">
                            <span class="flex items-center gap-1.5" title="CPF">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path></svg>
                                {{ $patient->cpf ?? 'Não informado' }}
                            </span>
                            
                            <span class="flex items-center gap-1.5" title="Data de Nascimento">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                {{ $patient->birth_date ? \Carbon\Carbon::parse($patient->birth_date)->format('d/m/Y') : 'Não informada' }}
                            </span>

                            @if($patient->agreement_number)
                                <span class="flex items-center gap-1.5 text-gray-400" title="Carteira">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                    {{ $patient->agreement_number }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                @hasanyrole('admin|manager|administrative')
                    <div class="flex-shrink-0 w-full sm:w-auto mt-4 sm:mt-0">
                        <button wire:click="$dispatch('abrir-modal-editar-paciente')" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg shadow-sm text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:text-blue-600 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            Editar Cadastro
                        </button>
                    </div>
                @endhasanyrole
            </div>

            @if($patient->patientServices && $patient->patientServices->isNotEmpty())
                <div class="mt-6 pt-5 border-t border-gray-100">
                    <h3 class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-3">Equipe de Acompanhamento</h3>
                    <div class="flex flex-wrap gap-3">
                        @foreach($patient->patientServices as $service)
                            <div class="flex items-center gap-2.5 bg-gray-50 border border-gray-200 rounded-md px-3 py-1.5 hover:bg-gray-100 transition-colors">
                                <div class="text-[10px] font-bold text-gray-600 uppercase tracking-wide">
                                    {{ $service->serviceType->name ?? 'Serviço' }}
                                </div>
                                <div class="h-3 w-px bg-gray-300"></div>
                                <div class="text-xs text-gray-600 flex items-center gap-2">
                                    <span title="Coordenador"><span class="text-gray-400 font-medium">Coord:</span> <span class="font-medium text-gray-700">{{ $service->coordinator->name ?? 'N/D' }}</span></span>
                                    <span class="text-gray-300">•</span>
                                    <span title="Supervisor"><span class="text-gray-400 font-medium">Sup:</span> <span class="font-medium text-gray-700">{{ $service->supervisor->name ?? 'N/D' }}</span></span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="bg-white px-6 border border-gray-200 rounded-xl shadow-sm">
        <nav class="-mb-px flex space-x-8 overflow-x-auto" aria-label="Tabs">
            <!-- <button wire:click="setAba('visao-geral')" 
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors focus:outline-none
                    {{ $abaAtual === 'visao-geral' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                Visão Geral
            </button> -->

            <button wire:click="setAba('agenda')" 
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors focus:outline-none
                    {{ $abaAtual === 'agenda' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                Agenda
            </button>

            <button wire:click="setAba('cargas-horarias')" 
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors focus:outline-none
                    {{ $abaAtual === 'cargas-horarias' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                Cargas Horárias
            </button>

            @hasanyrole('admin|administrative')
            <button wire:click="setAba('laudos')" 
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors focus:outline-none
                    {{ $abaAtual === 'laudos' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                Laudos e Documentos
            </button>
            @endhasanyrole
        </nav>
    </div>

    <div>
        @if($abaAtual === 'agenda')
            <livewire:pacientes.agenda :patient="$patient" />
        @elseif($abaAtual === 'cargas-horarias')
            <livewire:pacientes.carga-horaria :patient="$patient" />
        @elseif($abaAtual === 'laudos')
            <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                <h3 class="text-gray-500">Em Breve</h3>
            </div>
        @endif
    </div>

    <livewire:pacientes.edit :patient="$patient" />

    <div x-data="{
            show: false,
            type: 'success',
            message: '',
            showNotification(event) {
                this.type = event.detail.type;
                this.message = event.detail.message;
                this.show = true;
                // Esconde automaticamente após 4 segundos
                setTimeout(() => this.show = false, 4000);
            }
        }"
        @notify.window="showNotification($event)"
        x-show="show"
        x-transition:enter="transform ease-out duration-300 transition"
        x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-4"
        x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        style="display: none;"
        class="fixed bottom-6 right-6 z-[60] flex w-full max-w-sm overflow-hidden bg-white rounded-lg shadow-2xl border-l-4 pointer-events-auto"
        :class="type === 'success' ? 'border-green-500' : 'border-red-500'"
        x-cloak>
        
        <div class="flex items-start w-full p-4 border border-l-0 border-gray-100 rounded-r-lg">
            <div class="flex-shrink-0 mt-0.5">
                <svg x-show="type === 'success'" class="w-6 h-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <svg x-show="type === 'error'" class="w-6 h-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            
            <div class="ml-3 w-0 flex-1">
                <p class="text-sm font-bold text-gray-900" x-text="type === 'success' ? 'Sucesso!' : 'Atenção!'"></p>
                <p class="mt-1 text-sm text-gray-500 font-medium" x-text="message"></p>
            </div>
            
            <div class="ml-4 flex flex-shrink-0">
                <button @click="show = false" type="button" class="inline-flex text-gray-400 bg-white rounded-md hover:text-gray-600 focus:outline-none transition-colors">
                    <span class="sr-only">Fechar</span>
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

</div>