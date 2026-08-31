<div>
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 print:hidden">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Aniversariantes</h1>
            <p class="text-sm text-gray-500 mt-1">Profissionais e usuários do sistema, organizados por mês e dia.</p>
        </div>

        <div class="flex items-center gap-3">
            <select wire:model.live="unit_id" class="block w-full sm:w-56 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                <option value="">Todas as unidades</option>
                @foreach($unidadesFiltro as $unidade)
                    <option value="{{ $unidade->id }}">{{ $unidade->city ?? $unidade->name }}</option>
                @endforeach
            </select>

            <button type="button" onclick="window.print()" class="whitespace-nowrap inline-flex items-center gap-2 bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-md font-semibold text-sm hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Imprimir
            </button>

            <a href="{{ route('profissionais.index') }}" wire:navigate class="whitespace-nowrap bg-blue-600 text-white px-4 py-2 rounded-md font-semibold text-sm hover:bg-blue-700 transition-colors">
                Voltar
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach($meses as $numero => $mes)
            <div class="bg-white rounded-xl border overflow-hidden shadow-sm print:break-inside-avoid
                        {{ $numero === $mesAtual ? 'border-blue-300 ring-1 ring-blue-100' : 'border-gray-200' }}">
                <div class="px-4 py-3 border-b flex items-center justify-between
                            {{ $numero === $mesAtual ? 'bg-blue-50 border-blue-100' : 'bg-gray-50 border-gray-200' }}">
                    <h3 class="text-sm font-bold uppercase tracking-wide {{ $numero === $mesAtual ? 'text-blue-700' : 'text-gray-700' }}">
                        {{ $mes['nome'] }}
                    </h3>
                    @if($numero === $mesAtual)
                        <span class="text-[10px] font-bold uppercase tracking-wide text-blue-600 bg-blue-100 px-2 py-0.5 rounded-full">Mês atual</span>
                    @endif
                </div>

                <ul class="divide-y divide-gray-100">
                    @forelse($mes['profissionais'] as $profissional)
                        <li class="px-4 py-2.5 flex items-center gap-3">
                            <span class="shrink-0 w-9 text-center text-sm font-bold text-gray-400 tabular-nums">
                                {{ str_pad($profissional->birth_date->day, 2, '0', STR_PAD_LEFT) }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-gray-900 truncate flex items-center gap-1.5">
                                    {{ $profissional->name }}
                                    @if($profissional->rotulo)
                                        <span class="shrink-0 text-[10px] font-bold uppercase tracking-wide text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded">{{ $profissional->rotulo }}</span>
                                    @endif
                                </p>
                                @if($profissional->units->isNotEmpty())
                                    <p class="text-xs text-gray-400 truncate">{{ $profissional->units->pluck('city')->filter()->implode(', ') ?: $profissional->units->pluck('name')->implode(', ') }}</p>
                                @endif
                            </div>
                        </li>
                    @empty
                        <li class="px-4 py-6 text-center text-sm text-gray-400">Nenhum aniversariante</li>
                    @endforelse
                </ul>
            </div>
        @endforeach
    </div>

    <style>
        @media print {
            nav, aside, header { display: none !important; }
            body { background: white !important; }
        }
    </style>
</div>
