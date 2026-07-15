<div class="w-full h-full flex flex-col">
    
    @if($aniversariantes->isEmpty())
        <div class="flex-1 flex flex-col items-center justify-center py-8 text-center">
            <div class="bg-gray-100 p-3 rounded-full mb-3">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 15.546c-.523 0-1.046.151-1.5.454a2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.701 2.701 0 00-1.5-.454M9 6v2m3-2v2m3-2v2M9 3h.01M12 3h.01M15 3h.01M21 21v-7a2 2 0 00-2-2H5a2 2 0 00-2 2v7h18zm-3-9v-2a2 2 0 00-2-2H8a2 2 0 00-2 2v2h12z"></path></svg>
            </div>
            <h4 class="text-gray-700 font-medium">Nenhum aniversariante</h4>
            <p class="text-xs text-gray-500 mt-1">Nenhum paciente seu faz anos em {{ ucfirst($mesAtual) }}.</p>
        </div>
    @else
        <div class="space-y-3 flex-1 overflow-y-auto pr-2">
            @foreach($aniversariantes as $paciente)
                @php
                    $dataNascimento = \Carbon\Carbon::parse($paciente->birth_date);
                    $fazAnosHoje = $dataNascimento->isBirthday();
                @endphp

                <div class="flex items-center justify-between p-3 rounded-lg border {{ $fazAnosHoje ? 'bg-orange-50 border-orange-200 shadow-sm' : 'bg-white border-gray-100 hover:bg-gray-50' }} transition-colors">
                    
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full {{ $fazAnosHoje ? 'bg-orange-100 text-orange-600' : 'bg-gray-100 text-gray-500' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 15.546c-.523 0-1.046.151-1.5.454a2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.701 2.701 0 00-1.5-.454M9 6v2m3-2v2m3-2v2M9 3h.01M12 3h.01M15 3h.01M21 21v-7a2 2 0 00-2-2H5a2 2 0 00-2 2v7h18zm-3-9v-2a2 2 0 00-2-2H8a2 2 0 00-2 2v2h12z"></path></svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold {{ $fazAnosHoje ? 'text-orange-900' : 'text-gray-900' }} truncate max-w-[150px] sm:max-w-[200px]" title="{{ $paciente->name }}">
                                {{ $paciente->name }}
                            </h4>
                            <p class="text-xs {{ $fazAnosHoje ? 'text-orange-600 font-medium' : 'text-gray-500' }}">
                                {{ $fazAnosHoje ? 'É Hoje! 🎉' : $dataNascimento->format('d/m') }}
                        </div>
                    </div>

                </div>
            @endforeach
        </div>
    @endif
</div>