@php
    $moeda = fn ($v) => 'R$ ' . number_format($v, 2, ',', '.');
    $num   = fn ($v) => number_format($v, 0, ',', '.');

    $variacao = function ($atual, $anterior) {
        if ($anterior <= 0) return null;
        return (($atual - $anterior) / $anterior) * 100;
    };

    $sessoesSemValor = $semRegra->sum('sessoes') + $incompativeis->sum('sessoes');
    $totalPendencias = ($semRegra->count() ? 1 : 0)
        + ($incompativeis->count() ? 1 : 0)
        + ($regrasInvalidas->count() ? 1 : 0)
        + ($inativos->count() ? 1 : 0)
        + ($semCheckOut ? 1 : 0)
        + ($totais['glosados']['atendimentos'] ? 1 : 0)
        + ($glosa && $glosa->glosa > 0 ? 1 : 0);

    $picoHistorico = max(1, collect($historico)->max('sessoes'));
@endphp

<div>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Olá, {{ explode(' ', auth()->user()->name)[0] }}!</h1>
            <p class="mt-1 text-sm text-gray-500">
                Bem-vindo(a) ao painel de controle de Produção e RH.
                @if($parcial)
                    <span class="font-medium text-amber-600">Competência em andamento — números parciais.</span>
                @endif
            </p>
        </div>

        <div class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 shadow-sm">
            <label for="competencia" class="text-sm font-medium text-gray-500">Mês de apuração</label>
            <select id="competencia" wire:model.live="competencia"
                    class="cursor-pointer border-0 bg-transparent py-0 pl-1 pr-7 text-sm font-bold text-blue-600 focus:ring-0">
                @foreach($competencias as $valor => $rotulo)
                    <option value="{{ $valor }}">{{ $rotulo }}</option>
                @endforeach
            </select>
            <svg wire:loading wire:target="competencia" class="h-4 w-4 animate-spin text-blue-500" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
        </div>
    </div>

    <div wire:loading.class="opacity-50" wire:target="competencia" class="transition-opacity">

        <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-500">Total Estimado</h3>
                <p class="text-2xl font-bold text-gray-900">{{ $moeda($totais['valor_total']) }}</p>
                @php $v = $variacao($totais['valor_total'], $anterior['valor_total']); @endphp
                <p class="mt-2 flex flex-wrap items-center gap-1 text-xs text-gray-400">
                    @if(! is_null($v))
                        <span class="font-semibold {{ $v >= 0 ? 'text-green-600' : 'text-red-500' }}">
                            {{ $v >= 0 ? '+' : '' }}{{ number_format($v, 1, ',', '.') }}%
                        </span>
                        @if($parcial)
                            vs. {{ $rotuloAnterior }} até o dia {{ now()->day }}
                        @else
                            vs. {{ $rotuloAnterior }}
                        @endif
                    @else
                        Sem base de comparação
                    @endif
                </p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-500">Sessões Realizadas</h3>
                <p class="text-2xl font-bold text-blue-600">{{ $num($totais['sessoes']) }}</p>
                <p class="mt-2 text-xs text-gray-400">
                    {{ $num($totais['atendimentos']) }} atendimentos com check-in
                </p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-500">Profissionais com Produção</h3>
                <p class="text-2xl font-bold text-indigo-600">{{ $num($totais['profissionais']) }}</p>
                <p class="mt-2 text-xs text-gray-400">
                    {{ $num($totais['profissionais'] - $semRegra->count() - $incompativeis->count()) }} com valor apurado
                </p>
            </div>

            <div class="rounded-xl border p-5 shadow-sm {{ $sessoesSemValor > 0 ? 'border-amber-300 bg-amber-50' : 'border-gray-200 bg-white' }}">
                <h3 class="mb-2 text-xs font-bold uppercase tracking-wider {{ $sessoesSemValor > 0 ? 'text-amber-700' : 'text-gray-500' }}">
                    Sessões sem Valor
                </h3>
                <p class="text-2xl font-bold {{ $sessoesSemValor > 0 ? 'text-amber-700' : 'text-gray-900' }}">
                    {{ $num($sessoesSemValor) }}
                </p>
                <p class="mt-2 text-xs {{ $sessoesSemValor > 0 ? 'text-amber-700' : 'text-gray-400' }}">
                    @if($sessoesSemValor > 0)
                        Fecham em R$ 0,00 por falta de regra
                    @else
                        Toda produção do mês está precificada
                    @endif
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm lg:col-span-2">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="font-bold text-gray-800">Pendências de {{ $rotuloMes }}</h3>
                    @if($totalPendencias > 0)
                        <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-bold text-amber-700">
                            {{ $totalPendencias }} {{ $totalPendencias === 1 ? 'item' : 'itens' }}
                        </span>
                    @endif
                </div>

                @if($totalPendencias === 0)
                    <div class="flex flex-col items-center py-8 text-center">
                        <div class="mb-3 rounded-full bg-green-50 p-4">
                            <svg class="h-8 w-8 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h4 class="font-bold text-gray-800">Sem pendências</h4>
                        <p class="mt-1 max-w-sm text-sm text-gray-500">
                            Toda a produção da competência está precificada e com registro completo.
                        </p>
                    </div>
                @else
                    <div class="space-y-3">

                        @if($semRegra->count())
                            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-bold text-amber-900">
                                            {{ $semRegra->count() }} profissionais sem regra de pagamento
                                        </p>
                                        <p class="mt-0.5 text-xs text-amber-800">
                                            {{ $num($semRegra->sum('sessoes')) }} sessões que fecham em R$ 0,00.
                                        </p>
                                    </div>
                                    <a href="{{ route('producao.regras') }}" wire:navigate
                                       class="shrink-0 rounded-md bg-amber-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-amber-700">
                                        Cadastrar
                                    </a>
                                </div>
                                <details class="mt-3">
                                    <summary class="cursor-pointer text-xs font-semibold text-amber-800 hover:text-amber-900">
                                        Ver os 10 com mais sessões
                                    </summary>
                                    <ul class="mt-2 space-y-1 border-t border-amber-200 pt-2">
                                        @foreach($semRegra->sortByDesc('sessoes')->take(10) as $p)
                                            <li class="flex justify-between text-xs text-amber-900">
                                                <span class="truncate pr-2">{{ $p->nome }}</span>
                                                <span class="shrink-0 font-semibold">{{ $num($p->sessoes) }} sessões</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </details>
                            </div>
                        @endif

                        @if($incompativeis->count() || $regrasInvalidas->count())
                            <div class="rounded-lg border border-orange-200 bg-orange-50 p-4">
                                <p class="text-sm font-bold text-orange-900">
                                    {{ $incompativeis->count() }} profissionais com regra que não gera valor
                                </p>
                                <p class="mt-0.5 text-xs text-orange-800">
                                    {{ $num($incompativeis->sum('sessoes')) }} sessões. A regra existe, mas nenhuma casou com os
                                    atendimentos do mês ou o tipo de pagamento não é apurado por sessão.
                                </p>
                                @if($regrasInvalidas->count())
                                    <ul class="mt-2 space-y-1 border-t border-orange-200 pt-2">
                                        @foreach($regrasInvalidas as $regra)
                                            <li class="flex justify-between gap-2 text-xs text-orange-900">
                                                <span class="truncate">{{ $regra->professional->name ?? 'Profissional removido' }}</span>
                                                <span class="shrink-0 font-semibold">tipo "{{ $regra->payment_type }}" não é apurado</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        @endif

                        @if($inativos->count())
                            <div class="rounded-lg border border-purple-200 bg-purple-50 p-4">
                                <p class="text-sm font-bold text-purple-900">
                                    {{ $inativos->count() }} profissionais inativados com produção no mês
                                </p>
                                <p class="mt-0.5 text-xs text-purple-800">
                                    {{ $num($inativos->sum('sessoes')) }} sessões, {{ $moeda($inativos->sum('valor')) }}.
                                    Não aparecem na tela de Fechamento — confira o acerto de saída.
                                </p>
                                <ul class="mt-2 space-y-1 border-t border-purple-200 pt-2">
                                    @foreach($inativos->sortByDesc('sessoes') as $p)
                                        <li class="flex justify-between gap-2 text-xs text-purple-900">
                                            <span class="truncate">{{ $p->nome }}</span>
                                            <span class="shrink-0 font-semibold">
                                                {{ $num($p->sessoes) }} sessões · {{ $moeda($p->valor) }}
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if($semCheckOut)
                            <div class="flex items-start justify-between gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4">
                                <div>
                                    <p class="text-sm font-bold text-gray-800">
                                        {{ $num($semCheckOut) }} atendimentos sem check-out
                                    </p>
                                    <p class="mt-0.5 text-xs text-gray-600">
                                        Entram no repasse, mas o registro está incompleto e ficam de fora da CH.
                                    </p>
                                </div>
                                <a href="{{ route('producao.auditoria') }}" wire:navigate
                                   class="shrink-0 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-bold text-gray-700 hover:bg-gray-100">
                                    Auditar
                                </a>
                            </div>
                        @endif

                        @if($totais['glosados']['atendimentos'])
                            <div class="rounded-lg border border-red-200 bg-red-50 p-4">
                                <p class="text-sm font-bold text-red-900">
                                    {{ $num($totais['glosados']['atendimentos']) }} atendimentos marcados como glosados
                                </p>
                                <p class="mt-0.5 text-xs text-red-800">
                                    {{ $num($totais['glosados']['sessoes']) }} sessões excluídas do repasse.
                                </p>
                            </div>
                        @endif

                        @if($glosa)
                            <div class="flex items-start justify-between gap-3 rounded-lg border border-rose-200 bg-rose-50 p-4">
                                <div>
                                    <p class="text-sm font-bold text-rose-900">
                                        {{ $moeda($glosa->glosa) }} glosados pelo convênio
                                    </p>
                                    <p class="mt-0.5 text-xs text-rose-800">
                                        Competência {{ $glosa->competencia->format('m/Y') }}, a última com relatório
                                        importado — {{ number_format($glosa->apresentado > 0 ? $glosa->glosa / $glosa->apresentado * 100 : 0, 2, ',', '.') }}%
                                        do apresentado. O relatório chega cerca de dois meses depois do atendimento.
                                    </p>
                                </div>
                                <a href="{{ route('producao.glosas') }}" wire:navigate
                                   class="shrink-0 rounded-md bg-rose-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-rose-700">
                                    Analisar
                                </a>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 font-bold text-gray-800">Ações Rápidas</h3>

                <div class="space-y-3">
                    <a href="{{ route('producao.fechamento') }}" wire:navigate
                       class="group flex items-center justify-between rounded-lg border border-gray-100 p-3 transition-colors hover:border-blue-100 hover:bg-blue-50">
                        <div class="flex items-center gap-3">
                            <div class="rounded-md bg-blue-100 p-2 text-blue-600 transition-colors group-hover:bg-blue-200">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <span class="text-sm font-semibold text-gray-700 group-hover:text-blue-700">Apurar Fechamento</span>
                        </div>
                        <svg class="h-4 w-4 text-gray-300 group-hover:text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>

                    <a href="{{ route('producao.regras') }}" wire:navigate
                       class="group flex items-center justify-between rounded-lg border border-gray-100 p-3 transition-colors hover:border-emerald-100 hover:bg-emerald-50">
                        <div class="flex items-center gap-3">
                            <div class="rounded-md bg-emerald-100 p-2 text-emerald-600 transition-colors group-hover:bg-emerald-200">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 9v1m9-5a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <span class="text-sm font-semibold text-gray-700 group-hover:text-emerald-700">Regras de Pagamento</span>
                        </div>
                        @if($semRegra->count())
                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-700">{{ $semRegra->count() }}</span>
                        @else
                            <svg class="h-4 w-4 text-gray-300 group-hover:text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        @endif
                    </a>

                    <a href="{{ route('producao.glosas') }}" wire:navigate
                       class="group flex items-center justify-between rounded-lg border border-gray-100 p-3 transition-colors hover:border-rose-100 hover:bg-rose-50">
                        <div class="flex items-center gap-3">
                            <div class="rounded-md bg-rose-100 p-2 text-rose-600 transition-colors group-hover:bg-rose-200">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <span class="text-sm font-semibold text-gray-700 group-hover:text-rose-700">Glosas do Convênio</span>
                        </div>
                        <svg class="h-4 w-4 text-gray-300 group-hover:text-rose-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>

                    <a href="{{ route('producao.auditoria') }}" wire:navigate
                       class="group flex items-center justify-between rounded-lg border border-gray-100 p-3 transition-colors hover:bg-gray-50">
                        <div class="flex items-center gap-3">
                            <div class="rounded-md bg-gray-100 p-2 text-gray-600">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <span class="text-sm font-semibold text-gray-700">Auditar Atendimentos</span>
                        </div>
                        <svg class="h-4 w-4 text-gray-300 group-hover:text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>

                <h3 class="mb-3 mt-6 border-t border-gray-100 pt-5 font-bold text-gray-800">Sessões por Mês</h3>
                <div class="flex h-28 items-end justify-between gap-1.5">
                    @foreach($historico as $ponto)
                        <div class="flex flex-1 flex-col items-center gap-1.5"
                             title="{{ $ponto['titulo'] }}: {{ $num($ponto['sessoes']) }} sessões">
                            <div class="w-full rounded-t {{ $ponto['atual'] ? 'bg-blue-500' : 'bg-blue-200' }}"
                                 style="height: {{ max(2, (int) round($ponto['sessoes'] / $picoHistorico * 88)) }}px"></div>
                            <span class="text-[10px] font-medium {{ $ponto['atual'] ? 'text-blue-600' : 'text-gray-400' }}">
                                {{ $ponto['rotulo'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm lg:col-span-3">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="font-bold text-gray-800">Maiores Repasses de {{ $rotuloMes }}</h3>
                    <a href="{{ route('producao.fechamento') }}" wire:navigate
                       class="text-xs font-semibold text-blue-600 hover:text-blue-700">Ver todos</a>
                </div>

                @if($ranking->isEmpty())
                    <p class="py-6 text-center text-sm text-gray-500">
                        Nenhum repasse apurado nesta competência.
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 text-left text-xs uppercase tracking-wider text-gray-500">
                                    <th class="pb-2 font-bold">Profissional</th>
                                    <th class="pb-2 text-right font-bold">Sessões</th>
                                    <th class="pb-2 text-right font-bold">Valor</th>
                                    <th class="pb-2 pl-4 font-bold">Participação</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($ranking as $p)
                                    <tr>
                                        <td class="py-2.5 pr-4 font-medium text-gray-800">
                                            {{ $p->nome }}
                                            @if($p->inativo)
                                                <span class="ml-1 rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-bold text-gray-500">INATIVO</span>
                                            @endif
                                        </td>
                                        <td class="py-2.5 text-right tabular-nums text-gray-600">{{ $num($p->sessoes) }}</td>
                                        <td class="py-2.5 text-right font-bold tabular-nums text-gray-900">{{ $moeda($p->valor) }}</td>
                                        <td class="w-1/3 py-2.5 pl-4">
                                            <div class="h-1.5 w-full rounded-full bg-gray-100">
                                                <div class="h-1.5 rounded-full bg-blue-500"
                                                     style="width: {{ $totais['valor_total'] > 0 ? round($p->valor / $totais['valor_total'] * 100, 1) : 0 }}%"></div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
