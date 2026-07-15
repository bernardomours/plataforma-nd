<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Agenda Semanal</h1>
        <p class="text-sm text-gray-500 mt-1">Visualize os horários em formato de calendário, registre indisponibilidades e agende pacientes.</p>
    </div>

    <div class="py-8">
        <div class="max-w-[1400px] mx-auto space-y-6">
            
            @if (session()->has('message'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span class="font-medium text-sm">{{ session('message') }}</span>
                </div>
            @endif

            @if (session()->has('error'))
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center gap-2">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span class="font-medium text-sm">{{ session('error') }}</span>
                </div>
            @endif

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col md:flex-row justify-between items-end gap-4">
                <div class="flex-1 w-full max-w-xl">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Selecione o Profissional</label>
                    <select wire:model.live="professional_id" {{ $isRestricted ? 'disabled' : '' }} class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-gray-700 {{ $isRestricted ? 'bg-gray-100 cursor-not-allowed opacity-75' : '' }}">
                        @if(!$isRestricted)
                            <option value="">Buscar profissional...</option>
                        @endif
                        @foreach($profissionais as $profissional)
                            <option value="{{ $profissional->id }}">{{ $profissional->name }}</option>
                        @endforeach
                    </select>
                </div>

                @if($professional_id)
                    <div class="flex gap-3 w-full md:w-auto">
                        @if(!$isRestricted)
                            <button wire:click="openScheduleModal" class="flex-1 md:flex-initial px-5 py-2.5 bg-blue-600 text-white rounded-lg text-sm font-bold hover:bg-blue-700 transition-colors flex items-center justify-center gap-2 shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Agendar Paciente
                            </button>
                        @endif

                        <button wire:click="openBlockModal" class="flex-1 md:flex-initial px-5 py-2.5 bg-red-50 text-red-600 border border-red-200 rounded-lg text-sm font-bold hover:bg-red-100 transition-colors flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            Notificar Horário Indisponível
                        </button>
                    </div>
                @endif
            </div>

            @if($professional_id)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden overflow-x-auto">
                    <div class="min-w-[800px]">
                        
                        <!-- CABEÇALHO DOS DIAS DA SEMANA -->
                        <div class="grid grid-cols-[80px_repeat(5,1fr)] bg-gray-50 border-b border-gray-200">
                            <div class="py-3 px-2 border-r border-gray-200"></div>
                            @foreach($diasDaSemana as $numeroDia => $nomeDia)
                                <div class="py-3 px-2 text-center border-r border-gray-200 last:border-r-0">
                                    <span class="text-xs font-black text-gray-600 uppercase tracking-widest">{{ $nomeDia }}</span>
                                </div>
                            @endforeach
                        </div>

                        <!-- BLOQUEIOS DE "DIA INTEIRO" -->
                        @php
                            $temDiaInteiro = collect($agenda['DiaInteiro'])->flatten()->count() > 0;
                        @endphp
                        
                        @if($temDiaInteiro)
                            <div class="grid grid-cols-[80px_repeat(5,1fr)] border-b-2 border-gray-300 bg-red-50/20">
                                <div class="py-2 px-2 text-right border-r border-gray-200 flex flex-col justify-center">
                                    <span class="text-[10px] font-bold text-gray-500 uppercase leading-tight">Dia<br>Inteiro</span>
                                </div>
                                @foreach($diasDaSemana as $numeroDia => $nomeDia)
                                    <div class="p-1.5 border-r border-gray-200 last:border-r-0">
                                        @foreach($agenda['DiaInteiro'][$numeroDia] as $horario)
                                            <div class="p-2 mb-1 rounded bg-red-100 border border-red-300 flex items-center justify-between group relative">
                                                <div class="text-[10px] font-bold text-red-600 flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                                    INDISPONÍVEL
                                                </div>
                                                <button wire:click="removeBlock({{ $horario->id }})" wire:confirm="Liberar este dia inteiro?" class="p-1 bg-white rounded text-red-500 opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-50 shadow-sm" title="Liberar horário">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="bg-white border-t border-gray-200">
                            @foreach($agenda['Horarios'] as $hora => $dias)
                                @php
                                    $alturaLinha = ($hora === '18:00') ? 'h-[40px]' : 'h-[120px]';
                                @endphp
                                <div class="grid grid-cols-[80px_repeat(5,1fr)] border-b border-gray-100 group/row">
                                    
                                    <div class="py-2 px-3 text-right border-r border-gray-200 {{ $alturaLinha }}">
                                        <span class="text-[13px] font-bold text-gray-400 block -mt-1">{{ $hora }}</span>
                                    </div>
                                    
                                    @foreach($diasDaSemana as $numeroDia => $nomeDia)
                                        @php
                                            // VERIFICA SE O DIA ESTÁ BLOQUEADO POR INTEIRO NO TOPO
                                            $diaTodoBloqueado = isset($agenda['DiaInteiro'][$numeroDia]) && count($agenda['DiaInteiro'][$numeroDia]) > 0;
                                        @endphp
                                        
                                        <!-- Se estiver bloqueado, a coluna ganha o fundo vermelho suave -->
                                        <div class="border-r border-gray-200 last:border-r-0 {{ $alturaLinha }} relative {{ $diaTodoBloqueado ? 'bg-red-50/60' : '' }}">
                                            
                                            <!-- ZONA DE CLIQUE PARA NOVO AGENDAMENTO (Some se o dia estiver bloqueado) -->
                                            @if(empty($dias[$numeroDia]) && !$diaTodoBloqueado && !$isRestricted)
                                                <div wire:click="openScheduleModal({{ $numeroDia }}, '{{ $hora }}')" 
                                                     class="absolute inset-0 cursor-pointer hover:bg-blue-50/20 transition-all flex items-center justify-center group/empty z-0">
                                                    <span class="text-[11px] font-bold text-blue-500 bg-blue-100/80 px-2 py-1 rounded-md opacity-0 group-hover/empty:opacity-100 transition-all flex items-center gap-1 shadow-sm">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                                        Agendar
                                                    </span>
                                                </div>
                                            @endif

                                            @foreach($dias[$numeroDia] as $horario)
                                                @php
                                                    $start = \Carbon\Carbon::parse($horario->start_time);
                                                    $end = \Carbon\Carbon::parse($horario->end_time);
                                                    $diffInMinutes = $start->diffInMinutes($end);
                                                    $heightPx = $diffInMinutes * 2;
                                                    $offsetY = $start->format('i') * 2;
                                                @endphp

                                                @if($horario->is_blocked)
                                                    <div class="absolute left-1 right-1 p-2 rounded-lg border border-red-300 bg-red-50 hover:bg-red-100 transition-all shadow-md z-10 hover:z-20 flex flex-col overflow-hidden group/card"
                                                         style="height: calc({{ $heightPx }}px - 6px); top:calc({{ $offsetY }}px + 3px);">
                                                        <div class="text-[10px] font-bold text-red-600 mb-0.5 whitespace-nowrap">
                                                            {{ $start->format('H:i') }} - {{ $end->format('H:i') }}
                                                        </div>
                                                        <div class="text-[10px] font-black text-red-500 uppercase tracking-wider flex items-center gap-1">
                                                            <svg class="w-3 h-3 text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                                            Indisponível
                                                        </div>
                                                        <button wire:click="removeBlock({{ $horario->id }})" wire:confirm="Deseja liberar este horário?" class="absolute top-1 right-1 p-0.5 bg-white border border-red-200 rounded text-red-500 opacity-0 group-hover/card:opacity-100 transition-opacity hover:bg-red-50 shadow-sm">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                        </button>
                                                    </div>
                                                @else
                                                    <!-- CARTÃO DO PACIENTE COM BOTÕES DE EDIÇÃO -->
                                                    <div class="absolute left-1 right-1 p-2 rounded-lg border border-blue-300 bg-blue-50 hover:bg-blue-100 transition-all shadow-md z-10 hover:z-20 flex flex-col overflow-hidden group/patient"
                                                        style="height: calc({{ $heightPx }}px - 6px); top: calc({{ $offsetY }}px + 3px);">
                                                        <div class="flex justify-between items-start mb-1">
                                                            <div class="text-[10px] font-extrabold text-blue-900 whitespace-nowrap">
                                                                {{ $start->format('H:i') }} - {{ $end->format('H:i') }}
                                                            </div>
                                                            <div class="text-[9px] font-bold text-blue-600 uppercase">{{ $horario->therapy?->name ?? 'N/A' }}</div>
                                                        </div>
                                                        <div class="text-[10px] font-bold text-gray-700 leading-tight">
                                                            {{ mb_strtoupper($horario->patient?->name ?? 'Paciente Removido') }}
                                                        </div>
                                                        <div class="text-[9px] text-gray-500 mt-1 truncate pr-12">{{ $horario->serviceType?->name ?? 'Clínica' }}</div>
                                                        
                                                        @if(!$isRestricted)
                                                            <div class="absolute top-1 right-1 flex flex-row gap-1 opacity-0 group-hover/patient:opacity-100 transition-opacity">
                                                                <button wire:click="editSchedule({{ $horario->id }})" class="p-1 bg-white border border-blue-200 rounded text-blue-600 hover:bg-blue-50 shadow-sm" title="Editar Horário">
                                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                                                </button>
                                                                <button wire:click="deleteSchedule({{ $horario->id }})" wire:confirm="Tem certeza que deseja excluir este agendamento?" class="p-1 bg-white border border-red-200 rounded text-red-500 hover:bg-red-50 shadow-sm" title="Excluir Horário">
                                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                                </button>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif
                                                
                                            @endforeach
                                        </div>
                                    @endforeach
                                    
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-20 text-gray-500 bg-white rounded-xl border border-gray-200 shadow-sm mt-6">
                    <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <p class="text-lg font-medium text-gray-500">Selecione um profissional acima para visualizar a agenda.</p>
                </div>
            @endif

        </div>
    </div>

    <!-- ======================================================= -->
    <!-- MODAL 1: NOVO/EDITAR AGENDAMENTO DE PACIENTE -->
    <!-- ======================================================= -->
    @if($isScheduleModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-600 bg-opacity-50 transition-opacity backdrop-blur-sm" wire:click="closeScheduleModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100">
                    <form wire:submit.prevent="saveSchedule">
                        <div class="bg-white px-6 pt-6 pb-4">
                            <div class="flex justify-between items-center mb-5 border-b border-gray-100 pb-3">
                                <h3 class="text-lg leading-6 font-bold text-blue-600 flex items-center gap-2" id="modal-title">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    <!-- TÍTULO DINÂMICO -->
                                    {{ $editingScheduleId ? 'Editar Agendamento' : 'Novo Agendamento' }}
                                </h3>
                                <button type="button" wire:click="closeScheduleModal" class="text-gray-400 hover:text-gray-600 focus:outline-none bg-gray-50 hover:bg-gray-100 p-1 rounded-md transition-colors">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>

                            @if($errors->has('schedule_time'))
                                <div class="mb-4 bg-red-50 text-red-600 p-3 rounded-lg text-sm border border-red-100">
                                    {{ $errors->first('schedule_time') }}
                                </div>
                            @endif

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Paciente <span class="text-red-500">*</span></label>
                                    <select wire:model="patient_id" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        <option value="">Selecione o paciente...</option>
                                        @foreach($patients as $patient)
                                            <option value="{{ $patient->id }}">{{ mb_strtoupper($patient->name) }}</option>
                                        @endforeach
                                    </select>
                                    @error('patient_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Dia da Semana <span class="text-red-500">*</span></label>
                                    <select wire:model="schedule_day" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        <option value="">Selecione o dia</option>
                                        <option value="segunda">Segunda-feira</option>
                                        <option value="terca">Terça-feira</option>
                                        <option value="quarta">Quarta-feira</option>
                                        <option value="quinta">Quinta-feira</option>
                                        <option value="sexta">Sexta-feira</option>
                                    </select>
                                    @error('schedule_day') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Início <span class="text-red-500">*</span></label>
                                        <input wire:model="schedule_start_time" type="time" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        @error('schedule_start_time') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Término <span class="text-red-500">*</span></label>
                                        <input wire:model="schedule_end_time" type="time" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        @error('schedule_end_time') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Terapia <span class="text-red-500">*</span></label>
                                    <select wire:model="schedule_therapy_id" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        <option value="">Selecione a terapia</option>
                                        @foreach($therapies as $therapy)
                                            <option value="{{ $therapy->id }}">{{ $therapy->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('schedule_therapy_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Atendimento <span class="text-red-500">*</span></label>
                                    <select wire:model="schedule_service_type_id" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        <option value="">Selecione uma opção</option>
                                        @foreach($serviceTypes as $service)
                                            <option value="{{ $service->id }}">{{ $service->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('schedule_service_type_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 px-6 py-4 sm:flex sm:flex-row-reverse rounded-b-xl border-t border-gray-100 mt-2">
                            <button type="submit" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-5 py-2.5 bg-blue-600 text-sm font-semibold text-white hover:bg-blue-700 focus:outline-none sm:ml-3 sm:w-auto transition-colors">
                                Salvar Agendamento
                            </button>
                            <button type="button" wire:click="closeScheduleModal" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-5 py-2.5 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto transition-colors">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- ======================================================= -->
    <!-- MODAL 2: BLOQUEIO DE HORÁRIO -->
    <!-- ======================================================= -->
    @if($isBlockModalOpen)
        <!-- (CÓDIGO MANTIDO INTACTO) -->
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-600 bg-opacity-50 transition-opacity backdrop-blur-sm" wire:click="closeBlockModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-100">
                    <form wire:submit.prevent="saveBlock">
                        <div class="bg-white px-6 pt-6 pb-4">
                            <div class="flex justify-between items-center mb-5 border-b border-gray-100 pb-3">
                                <h3 class="text-lg leading-6 font-bold text-red-600 flex items-center gap-2" id="modal-title">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                    Notificar Horário Indisponível
                                </h3>
                                <button type="button" wire:click="closeBlockModal" class="text-gray-400 hover:text-gray-600 focus:outline-none bg-gray-50 hover:bg-gray-100 p-1 rounded-md transition-colors">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>

                            @if($errors->has('block_time'))
                                <div class="mb-4 bg-red-50 text-red-600 p-3 rounded-lg text-sm border border-red-100">
                                    {{ $errors->first('block_time') }}
                                </div>
                            @endif

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Dia da Semana <span class="text-red-500">*</span></label>
                                    <select wire:model="block_day" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        <option value="">Selecione o dia</option>
                                        <option value="segunda">Segunda-feira</option>
                                        <option value="terca">Terça-feira</option>
                                        <option value="quarta">Quarta-feira</option>
                                        <option value="quinta">Quinta-feira</option>
                                        <option value="sexta">Sexta-feira</option>
                                    </select>
                                    @error('block_day') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div class="pt-1">
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input wire:model.live="block_whole_day" type="checkbox" class="w-4 h-4 text-red-600 bg-white border-gray-300 rounded focus:ring-red-500">
                                        <span class="ml-2 text-sm font-bold text-gray-700">Bloquear o dia inteiro</span>
                                    </label>
                                </div>

                                <div class="grid grid-cols-2 gap-4 transition-all duration-200 {{ $block_whole_day ? 'opacity-40 pointer-events-none grayscale' : '' }}">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Início <span class="text-red-500">*</span></label>
                                        <input wire:model="block_start_time" type="time" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        @error('block_start_time') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Término <span class="text-red-500">*</span></label>
                                        <input wire:model="block_end_time" type="time" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        @error('block_end_time') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 px-6 py-4 sm:flex sm:flex-row-reverse rounded-b-xl border-t border-gray-100 mt-2">
                            <button type="submit" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-5 py-2.5 bg-red-600 text-sm font-semibold text-white hover:bg-red-700 focus:outline-none sm:ml-3 sm:w-auto transition-colors">
                                Confirmar Bloqueio
                            </button>
                            <button type="button" wire:click="closeBlockModal" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-5 py-2.5 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto transition-colors">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>