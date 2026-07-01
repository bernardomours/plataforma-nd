<div>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
            Agenda Semanal
        </h2>
        <div class="text-sm text-gray-500 mt-1">
            Visualize os horários e pacientes vinculados a cada profissional.
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Selecione o Profissional</label>
                <select wire:model.live="professional_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-gray-700">
                    <option value="">Buscar profissional...</option>
                    @foreach($profissionais as $profissional)
                        <option value="{{ $profissional->id }}">{{ $profissional->name }}</option>
                    @endforeach
                </select>
            </div>

            @if($professional_id)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    
                    @foreach(['Manhã', 'Tarde'] as $turno)
                        <div class="bg-gray-50 px-5 py-3 border-b border-gray-200">
                            <h3 class="text-lg font-bold text-gray-900 uppercase tracking-wider">
                                {{ $turno }}
                            </h3>
                        </div>

                        <div class="grid grid-cols-5 divide-x divide-gray-200 border-b border-gray-200">
                            
                            @foreach($diasDaSemana as $numeroDia => $nomeDia)
                                <div class="flex flex-col bg-white">
                                    <div class="text-center py-2 border-b border-gray-100 bg-gray-50">
                                        <span class="text-[11px] font-bold text-gray-500 uppercase tracking-widest">{{ $nomeDia }}</span>
                                    </div>

                                    <div class="p-2 flex-1 min-h-[140px]">
                                        @forelse($agenda[$turno][$numeroDia] as $horario)
                                            <div class="mb-2 p-2 rounded-lg border border-gray-200 bg-white shadow-sm hover:shadow transition-shadow flex flex-col">
                                                
                                                <div class="flex justify-between items-start mb-2">
                                                    <div class="text-xs font-extrabold text-gray-900 whitespace-nowrap">
                                                        {{ \Carbon\Carbon::parse($horario->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($horario->end_time)->format('H:i') }}
                                                    </div>
                                                    <div class="text-[10px] text-right leading-tight ml-1">
                                                        <div class="font-black text-blue-600 uppercase">{{ $horario->therapy->name ?? '' }}</div>
                                                        <div class="text-gray-500">{{ $horario->serviceType->name ?? 'Clínica' }}</div>
                                                    </div>
                                                </div>
                                                
                                                <div class="text-[11px] font-bold text-gray-700 truncate flex items-center gap-1">
                                                    <svg class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path></svg>
                                                    {{ mb_strtoupper($horario->patient->name ?? 'Sem paciente') }}
                                                </div>
                                                
                                            </div>
                                        @empty
                                            <div class="h-full flex items-center justify-center p-2 text-center mt-4">
                                                <span class="text-xs text-gray-400">Livre</span>
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            @endforeach
                            
                        </div>
                    @endforeach

                </div>
            @else
                <div class="flex flex-col items-center justify-center py-20 text-gray-500 bg-white rounded-xl border border-gray-200 shadow-sm mt-6">
                    <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <p class="text-lg font-medium text-gray-500">Selecione um profissional acima para visualizar a agenda.</p>
                </div>
            @endif

        </div>
    </div>
</div>