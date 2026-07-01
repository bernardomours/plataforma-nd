<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-5 border-b border-gray-100 bg-white">
            <h3 class="text-lg font-bold text-gray-900">Pacientes</h3>
            <p class="text-sm text-gray-500 mt-1 flex items-center gap-1">
                🎂 Aniversariantes do Dia
            </p>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50/50 border-b border-gray-100">
                    <tr>
                        <th class="py-3 px-5 font-semibold text-gray-700">Nome</th>
                        <th class="py-3 px-5 font-semibold text-gray-700 text-right">Unidade</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($pacientes as $paciente)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="py-3 px-5 font-bold text-gray-900 flex items-center gap-2 uppercase text-xs">
                                <span class="text-blue-500">🎂</span>
                                {{ $paciente->name }}
                            </td>
                            <td class="py-3 px-5 text-right">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                                    {{ $paciente->unit->city ?? 'Sem unidade' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="py-8 text-center text-gray-400 text-sm">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-8 h-8 mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    Ninguém soprando velinhas hoje
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-5 border-b border-gray-100 bg-white">
            <h3 class="text-lg font-bold text-gray-900">Equipe</h3>
            <p class="text-sm text-gray-500 mt-1 flex items-center gap-1">
                🎂 Aniversariantes do Dia
            </p>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50/50 border-b border-gray-100">
                    <tr>
                        <th class="py-3 px-5 font-semibold text-gray-700">Nome</th>
                        <th class="py-3 px-5 font-semibold text-gray-700 text-right">Unidade</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($equipe as $membro)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="py-3 px-5 font-bold text-gray-900 flex items-center gap-2 text-xs">
                                <span class="text-blue-500">🎂</span>
                                {{ $membro->name }}
                            </td>
                            <td class="py-3 px-5 text-right">
                                @php
                                    $cidades = $membro->units ? $membro->units->pluck('city')->join(', ') : 'Sem unidade';
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                                    {{ $cidades ?: 'Sem unidade' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="py-8 text-center text-gray-400 text-sm">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-8 h-8 mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    Ninguém soprando velinhas hoje
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>