    <div class="w-full">
        
        @if($agendamentos->isEmpty())
            <div class="flex flex-col items-center justify-center py-8 text-center">
                <div class="bg-gray-100 p-3 rounded-full mb-3">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h4 class="text-gray-700 font-medium">Nenhum atendimento para hoje</h4>
                <p class="text-xs text-gray-500 mt-1">Aproveite o tempo livre na sua {{ $diaSemana }}.</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($agendamentos as $horario)
                    @php
                        $horaInicio = \Carbon\Carbon::parse($horario->start_time)->format('H:i');
                        $horaFim = \Carbon\Carbon::parse($horario->end_time)->format('H:i');
                        $isPassado = \Carbon\Carbon::parse($horario->end_time)->isPast();
                    @endphp

                    <div class="flex items-start gap-4 p-3 rounded-lg border {{ $isPassado ? 'bg-gray-50 border-gray-100 opacity-60' : 'bg-white border-blue-100 shadow-sm hover:shadow-md transition-shadow' }}">
                        
                        <div class="flex flex-col items-center justify-center min-w-[60px]">
                            <span class="text-sm font-bold {{ $isPassado ? 'text-gray-500' : 'text-blue-600' }}">{{ $horaInicio }}</span>
                            <span class="text-[10px] text-gray-400 font-medium">{{ $horaFim }}</span>
                        </div>

                        <div class="w-1 rounded-full {{ $isPassado ? 'bg-gray-300' : 'bg-blue-400' }} self-stretch"></div>

                        <div class="flex-1">
                            <h4 class="text-sm font-bold text-gray-900 truncate" title="{{ $horario->patient?->name ?? 'Paciente Removido' }}">
                                {{ $horario->patient?->name ?? 'Paciente Indefinido/Removido' }}
                            </h4>
                            
                            <div class="flex flex-wrap items-center gap-2 mt-1.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                    {{ $horario->therapy?->name ?? 'Terapia' }}
                                </span>
                                
                                @if($horario->serviceType)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-gray-100 text-gray-700">
                                        {{ $horario->serviceType?->name }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        
                    </div>
                @endforeach
            </div>
        @endif
    </div>