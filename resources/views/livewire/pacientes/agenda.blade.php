<div>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            
            <a href="{{ route('pacientes.index') }}" wire:navigate class="p-2 -ml-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500" title="Voltar para a lista de pacientes">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            
            <div class="flex flex-col">
                <nav class="flex text-xs text-gray-500 mb-1" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1">
                        <li>
                            <a href="{{ route('pacientes.index') }}" wire:navigate class="hover:text-blue-600 transition-colors">Pacientes</a>
                        </li>
                        <li><span class="mx-1 text-gray-400">/</span></li>
                        <li aria-current="page" class="text-gray-700 font-medium">Agenda</li>
                    </ol>
                </nav>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Agenda
                </h2>
            </div>

        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">

        @if (session()->has('message'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" 
                 class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center gap-2">
                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span class="font-medium text-sm">{{ session('message') }}</span>
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            
            <div class="p-6 border-b border-gray-200 flex justify-between items-start">
                <div>
                    <h3 class="text-2xl font-bold text-gray-800 uppercase">{{ $patient->name }}</h3>
                    <p class="text-lg text-gray-500 mt-1">Acompanhamento de horários</p>
                </div>
                <div>
                    <button wire:click="openModal" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-semibold hover:bg-blue-700 transition shadow-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Adicionar Horário
                    </button>
                </div>
            </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            @php
                $daysOfWeek = [
                    'segunda' => 'SEGUNDA',
                    'terca'   => 'TERÇA',
                    'quarta'  => 'QUARTA',
                    'quinta'  => 'QUINTA',
                    'sexta'   => 'SEXTA',
                ];
            @endphp

            <div class="grid grid-cols-5 divide-x divide-gray-200">
                @foreach ($daysOfWeek as $key => $dayName)
                    <div>
                        <div class="py-3 px-4 bg-gray-50 border-b border-gray-200">
                            <h3 class="text-xs font-bold text-center text-gray-600 tracking-wider">{{ $dayName }}</h3>
                        </div>

                        <div class="p-4 space-y-3 min-h-[300px]">
                            @php
                                $daySchedules = $schedulesGrouped->get($key) ?? collect();
                            @endphp

                            @forelse ($daySchedules as $schedule)
                                <div class="bg-blue-50/60 border-l-4 border-blue-500 rounded-r-lg p-3 relative group transition-all hover:shadow-md hover:bg-blue-50">
                                    <div class="pr-6"> <p class="font-bold text-blue-800 text-xs mb-1">
                                            {{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}
                                        </p>
                                        <p class="font-bold text-gray-900 text-sm leading-tight">{{ $schedule->therapy?->name ?? 'N/A' }}</p>
                                        <p class="text-xs text-gray-600 mt-1">{{ $schedule->serviceType?->name ?? 'Ambiente não definido' }}</p> 
                                        <p class="text-xs text-gray-500 mt-1 truncate">{{ $schedule->professional?->name ?? 'N/A' }}</p>
                                    </div>

                                    <div class="absolute top-2 right-2 flex flex-col gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button wire:click="editSchedule({{ $schedule->id }})" class="text-orange-500 hover:text-orange-700 bg-white rounded-full p-1 shadow-sm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                        </button>
                                        <button wire:click="deleteSchedule({{ $schedule->id }})" wire:confirm="Tem certeza que deseja excluir este horário?" class="text-red-500 hover:text-red-700 bg-white rounded-full p-1 shadow-sm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div class="flex items-center justify-center h-full pt-8 pb-4">
                                    <p class="text-xs text-gray-400">Sem agendamentos</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    @if($isModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                
                <div class="fixed inset-0 bg-gray-600 bg-opacity-50 transition-opacity backdrop-blur-sm" wire:click="closeModal"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100">
                    <form wire:submit.prevent="saveSchedule">
                        <div class="bg-white px-6 pt-6 pb-4">
                            <div class="flex justify-between items-center mb-5">
                                <h3 class="text-lg leading-6 font-bold text-gray-900" id="modal-title">
                                    {{ $editingScheduleId ? 'Editar Horário' : 'Novo Horário' }}
                                </h3>
                                <button type="button" wire:click="closeModal" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>

                            <div class="space-y-5">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Dia da Semana <span class="text-red-500">*</span></label>
                                    <select wire:model="day_of_week" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        <option value="">Selecione</option>
                                        <option value="segunda">Segunda-feira</option>
                                        <option value="terca">Terça-feira</option>
                                        <option value="quarta">Quarta-feira</option>
                                        <option value="quinta">Quinta-feira</option>
                                        <option value="sexta">Sexta-feira</option>
                                    </select>
                                    @error('day_of_week') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Hora de Início <span class="text-red-500">*</span></label>
                                        <input wire:model="start_time" type="time" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        @error('start_time') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Hora de Fim <span class="text-red-500">*</span></label>
                                        <input wire:model="end_time" type="time" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        @error('end_time') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Profissional <span class="text-red-500">*</span></label>
                                    <select wire:model="professional_id" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        <option value="">Selecione</option>
                                        @foreach($professionals as $prof)
                                            <option value="{{ $prof->id }}">{{ $prof->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('professional_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Terapia <span class="text-red-500">*</span></label>
                                    <select wire:model="therapy_id" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        <option value="">Selecione</option>
                                        @foreach($therapies as $therapy)
                                            <option value="{{ $therapy->id }}">{{ $therapy->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('therapy_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Atendimento <span class="text-red-500">*</span></label>
                                    <select wire:model="service_type_id" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        <option value="">Selecione uma opção</option>
                                        @foreach($serviceTypes as $service)
                                            <option value="{{ $service->id }}">{{ $service->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('service_type_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 px-6 py-4 sm:flex sm:flex-row-reverse rounded-b-xl border-t border-gray-100">
                            <button type="submit" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-5 py-2.5 bg-blue-600 text-sm font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto transition-colors">
                                Enviar
                            </button>
                            <button type="button" wire:click="closeModal" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-5 py-2.5 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto transition-colors">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>