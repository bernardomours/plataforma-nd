<div>
    <!-- Notificação Toast -->
    @if (session()->has('message'))
        <div x-data="{ show: true }" 
             x-show="show" 
             x-init="setTimeout(() => show = false, 3000)" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform translate-y-2"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform translate-y-0"
             x-transition:leave-end="opacity-0 transform translate-y-2"
             class="fixed bottom-4 right-4 z-50 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center gap-2 shadow-lg">
            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <span class="font-medium text-sm">{{ session('message') }}</span>
        </div>
    @endif

    <!-- Container Principal do Módulo -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        
        <!-- Cabeçalho Interno da Aba -->
        <div class="p-6 border-b border-gray-200 flex justify-between items-start">
            <div>
                <h3 class="text-lg font-bold text-gray-900 uppercase">Quadro de Horários Fixos</h3>
                <p class="text-sm text-gray-500 mt-1">Acompanhamento da agenda semanal do paciente.</p>
            </div>
            @hasanyrole('admin|manager|administrative')
                <div>
                    <button wire:click="openModal" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-semibold hover:bg-blue-700 transition shadow-sm flex items-center gap-2 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Novo Horário
                    </button>
                </div>
            @endhasanyrole
        </div>

        <!-- Grid de Semanas -->
        <div class="bg-white overflow-hidden">
            @php
                $daysOfWeek = [
                    'segunda' => 'SEGUNDA',
                    'terca'   => 'TERÇA',
                    'quarta'  => 'QUARTA',
                    'quinta'  => 'QUINTA',
                    'sexta'   => 'SEXTA',
                ];
            @endphp

            <div class="grid grid-cols-5 divide-x divide-gray-200 border-b border-gray-200">
                @foreach ($daysOfWeek as $key => $dayName)
                    <div>
                        <div class="py-3 px-4 bg-gray-50 border-b border-gray-200">
                            <h3 class="text-xs font-bold text-center text-gray-600 tracking-wider">{{ $dayName }}</h3>
                        </div>

                        <div class="p-4 space-y-3 min-h-[300px] bg-white">
                            @php
                                $daySchedules = $schedulesGrouped->get($key) ?? collect();
                            @endphp

                            @forelse ($daySchedules as $schedule)
                                <div class="bg-blue-50/60 border-l-4 border-blue-500 rounded-r-lg p-3 relative group transition-all hover:shadow-md hover:bg-blue-50">
                                    <div class="pr-6"> 
                                        <p class="font-bold text-blue-800 text-xs mb-1 flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            {{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}
                                        </p>
                                        <p class="font-bold text-gray-900 text-sm leading-tight">{{ $schedule->therapy?->name ?? 'N/A' }}</p>
                                        <p class="text-[11px] font-semibold text-gray-500 mt-1 uppercase">{{ $schedule->serviceType?->name ?? 'Ambiente não definido' }}</p> 
                                        <p class="text-xs text-gray-600 mt-1 truncate">{{ $schedule->professional?->name ?? 'N/A' }}</p>
                                    </div>

                                    @hasanyrole('admin|manager|administrative')
                                        <div class="absolute top-2 right-2 flex flex-col gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button wire:click="editSchedule({{ $schedule->id }})" class="text-orange-500 hover:text-orange-700 bg-white border border-gray-200 rounded-md p-1 shadow-sm transition-colors" title="Editar Horário">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                            </button>
                                            <button wire:click="deleteSchedule({{ $schedule->id }})" wire:confirm="Tem certeza que deseja excluir este horário?" class="text-red-500 hover:text-red-700 bg-white border border-gray-200 rounded-md p-1 shadow-sm transition-colors" title="Excluir Horário">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </div>
                                    @endhasanyrole
                                </div>
                            @empty
                                <div class="flex flex-col items-center justify-center h-full pt-8 pb-4 opacity-50">
                                    <svg class="w-6 h-6 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    <p class="text-xs text-gray-400 font-medium">Livre</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Modal de Formulário -->
    <div x-data="{ open: $wire.entangle('isModalOpen') }" 
         x-show="open" 
         style="display: none;" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         aria-labelledby="modal-title" 
         role="dialog" 
         aria-modal="true">
        
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            
            <div x-show="open"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-600 bg-opacity-75 transition-opacity backdrop-blur-sm" 
                 wire:click="closeModal"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="open"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                
                <form wire:submit.prevent="saveSchedule">
                    <div class="bg-white px-6 pt-6 pb-4">
                        <div class="flex justify-between items-center mb-5 border-b pb-3">
                            <h3 class="text-lg leading-6 font-bold text-gray-900" id="modal-title">
                                {{ $editingScheduleId ? 'Editar Horário' : 'Novo Horário' }}
                            </h3>
                            <button type="button" wire:click="closeModal" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                                <span class="sr-only">Fechar</span>
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Dia da Semana <span class="text-red-500">*</span></label>
                                <select wire:model="day_of_week" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="">Selecione</option>
                                    <option value="segunda">Segunda-feira</option>
                                    <option value="terca">Terça-feira</option>
                                    <option value="quarta">Quarta-feira</option>
                                    <option value="quinta">Quinta-feira</option>
                                    <option value="sexta">Sexta-feira</option>
                                </select>
                                @error('day_of_week') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Hora de Início <span class="text-red-500">*</span></label>
                                    <input wire:model="start_time" type="time" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    @error('start_time') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Hora de Fim <span class="text-red-500">*</span></label>
                                    <input wire:model="end_time" type="time" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    @error('end_time') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Terapia <span class="text-red-500">*</span></label>
                                <select wire:model.live="therapy_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="">Selecione</option>
                                    @foreach($therapies as $therapy)
                                        <option value="{{ $therapy->id }}">{{ $therapy->name }}</option>
                                    @endforeach
                                </select>
                                @error('therapy_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div class="relative" wire:key="prof-container-{{ $therapy_id ?? 'empty' }}">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Profissional <span class="text-red-500">*</span></label>
                                
                                <select wire:model="professional_id" 
                                        @if(empty($therapy_id)) disabled @endif
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm disabled:bg-gray-100 disabled:text-gray-400">
                                    
                                    <option value="">{{ $therapy_id ? 'Selecione' : 'Selecione a terapia primeiro' }}</option>
                                    
                                    @foreach($professionals as $prof)
                                        <option value="{{ $prof->id }}">{{ $prof->name }}</option>
                                    @endforeach
                                </select>
                                
                                <div wire:loading wire:target="therapy_id" class="absolute right-0 top-0 mt-1 mr-2">
                                    <svg class="animate-spin h-4 w-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                                
                                @error('professional_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Atendimento <span class="text-red-500">*</span></label>
                                <select wire:model="service_type_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="">Selecione uma opção</option>
                                    @foreach($serviceTypes as $service)
                                        <option value="{{ $service->id }}">{{ $service->name }}</option>
                                    @endforeach
                                </select>
                                @error('service_type_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                    
                    <!-- Rodapé do Modal -->
                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end gap-3 rounded-b-xl">
                        <button type="button" wire:click="closeModal" class="px-4 py-2 border border-gray-300 text-gray-700 bg-white rounded-md hover:bg-gray-100 text-sm font-semibold transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Cancelar
                        </button>

                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-md hover:bg-blue-700 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Salvar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>