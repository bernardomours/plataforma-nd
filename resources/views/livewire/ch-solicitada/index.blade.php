<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">CH - Solicitada</h1>
        <p class="text-sm text-gray-500 mt-1">Registro de todas as sessões solicitadas, liberadas e planejadas</p>
    </div>

    <div class="max-w-full mx-auto py-6 sm:px-6 lg:px-8">

        {{-- ══════════════ Indicadores ══════════════
             Os campos requested/approved/planned são contagens de SESSÕES, não horas.
             Os rótulos foram ajustados para refletir isso: a tela exibia "Horas" em
             valores que sempre foram sessões, e ainda misturava a realizada em formato
             de relógio (00:40) com as demais em número — o que impedia qualquer
             comparação direta entre as colunas.                                        --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col justify-between relative overflow-hidden">
                <div class="absolute right-0 top-0 w-2 h-full bg-orange-400"></div>
                <h3 class="text-sm font-semibold text-gray-500 mb-2">Sessões Solicitadas</h3>
                <p class="text-3xl font-bold text-gray-900 mb-3">{{ number_format($stats->solicitadas, 0, ',', '.') }}</p>
                <div class="flex items-center text-sm text-orange-500 font-medium gap-1">
                    <span>Total pedido ao convênio</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col justify-between relative overflow-hidden">
                <div class="absolute right-0 top-0 w-2 h-full bg-green-500"></div>
                <h3 class="text-sm font-semibold text-gray-500 mb-2">Sessões Autorizadas</h3>
                <p class="text-3xl font-bold text-gray-900 mb-3">{{ number_format($stats->aprovadas, 0, ',', '.') }}</p>
                <div class="flex items-center text-sm text-green-600 font-medium gap-1">
                    <span>Total liberado pelo convênio</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col justify-between relative overflow-hidden">
                <div class="absolute right-0 top-0 w-2 h-full bg-blue-500"></div>
                <h3 class="text-sm font-semibold text-gray-500 mb-2">Sessões Planejadas</h3>
                <p class="text-3xl font-bold text-gray-900 mb-3">{{ number_format($stats->planejadas, 0, ',', '.') }}</p>
                <div class="flex items-center text-sm text-blue-500 font-medium gap-1">
                    <span>Equivalente mensal (semanal × {{ $semanasNoMes }})</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col justify-between relative overflow-hidden">
                <div class="absolute right-0 top-0 w-2 h-full bg-purple-500"></div>
                <h3 class="text-sm font-semibold text-gray-500 mb-2">Sessões Realizadas</h3>
                <p class="text-3xl font-bold text-gray-900 mb-3">{{ number_format($stats->realizadas, 0, ',', '.') }}</p>
                <div class="flex items-center text-sm text-purple-600 font-medium gap-1">
                    <span>{{ number_format($stats->atendimentos, 0, ',', '.') }} atendimentos · {{ $this->formatTime($stats->horas_realizadas) }}</span>
                </div>
            </div>
        </div>

        {{-- ══════════════ Análise de faltas: Realizada × Planejada ══════════════ --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6 overflow-hidden">

            <div class="px-6 py-4 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold text-gray-900">Análise de Faltas</h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Sessões realizadas comparadas às planejadas no período
                        <span class="text-gray-400">· planejamento semanal convertido para o mês (× {{ $semanasNoMes }})</span>
                    </p>
                </div>

                @if($faixa ?? false)
                    <button wire:click="filtrarPorFaixa('{{ $faixa }}')" type="button"
                            class="inline-flex items-center gap-1.5 rounded-md bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-200 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        Remover filtro de faixa
                    </button>
                @endif
            </div>

            {{-- Aviso de cobertura: sem isto, um painel vazio parece defeito do sistema
                 quando na verdade é campo não preenchido no cadastro da CH. --}}
            @if($stats->registros > 0 && $stats->sem_plano > 0)
                <div class="px-6 py-3 bg-amber-50 border-b border-amber-200 flex items-start gap-2.5">
                    <svg class="w-4 h-4 text-amber-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <p class="text-xs text-amber-800 leading-relaxed">
                        <span class="font-semibold">{{ number_format($stats->sem_plano, 0, ',', '.') }}</span>
                        de {{ number_format($stats->registros, 0, ',', '.') }} registros
                        ({{ 100 - $stats->cobertura }}%) estão <span class="font-semibold">sem sessões planejadas</span> informadas
                        e por isso ficam fora deste cálculo.
                        <button wire:click="filtrarPorFaixa('sem_plano')" type="button" class="underline font-semibold hover:text-amber-950">
                            Ver esses registros
                        </button>
                    </p>
                </div>
            @endif

            @if($stats->com_plano > 0)
                <div class="grid grid-cols-1 lg:grid-cols-3 divide-y lg:divide-y-0 lg:divide-x divide-gray-200">

                    {{-- Aderência geral --}}
                    <div class="p-6">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Aderência ao planejado</h3>

                        @php
                            $ad = $stats->aderencia ?? 0;
                            $corBarra = $ad >= 100 ? 'bg-green-500' : ($ad >= 80 ? 'bg-yellow-500' : ($ad >= 50 ? 'bg-orange-500' : 'bg-red-500'));
                            $corTexto = $ad >= 100 ? 'text-green-600' : ($ad >= 80 ? 'text-yellow-600' : ($ad >= 50 ? 'text-orange-600' : 'text-red-600'));
                        @endphp

                        <div class="flex items-baseline gap-2 mb-3">
                            <span class="text-4xl font-bold {{ $corTexto }}">{{ number_format($ad, 1, ',', '.') }}%</span>
                        </div>

                        {{-- Barra de progresso: capada em 100% para não estourar o traçado,
                             mas o número acima mostra o valor real mesmo acima de 100%. --}}
                        <div class="w-full h-2.5 bg-gray-100 rounded-full overflow-hidden mb-3">
                            <div class="h-full {{ $corBarra }} rounded-full transition-all" style="width: {{ min($ad, 100) }}%"></div>
                        </div>

                        <p class="text-xs text-gray-500 leading-relaxed">
                            {{ number_format($stats->realizadas_com_plano, 0, ',', '.') }} sessões realizadas de
                            {{ number_format($stats->planejadas, 0, ',', '.') }} planejadas
                            <span class="text-gray-400">({{ number_format($stats->com_plano, 0, ',', '.') }} registros)</span>
                        </p>
                    </div>

                    {{-- Déficit e excedente --}}
                    <div class="p-6">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Sessões não realizadas</h3>

                        <div class="flex items-baseline gap-2 mb-1">
                            <span class="text-4xl font-bold text-red-600">{{ number_format($stats->deficit, 0, ',', '.') }}</span>
                            <span class="text-sm text-gray-400 font-medium">sessões</span>
                        </div>
                        <p class="text-xs text-gray-500 mb-4">Soma do que faltou em cada registro abaixo do planejado</p>

                        @if($stats->excedente > 0)
                            <div class="pt-3 border-t border-gray-100">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-500">Realizado acima do planejado</span>
                                    <span class="text-sm font-bold text-blue-600">+{{ number_format($stats->excedente, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Distribuição por faixa --}}
                    <div class="p-6">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">
                            Distribuição
                            <span class="normal-case font-normal text-gray-400">— clique para filtrar</span>
                        </h3>

                        <div class="space-y-2">
                            @foreach($faixas as $chave => $config)
                                @php
                                    $qtd = $stats->{'faixa_' . $chave} ?? 0;
                                    $pct = $stats->com_plano > 0 ? ($qtd / $stats->com_plano) * 100 : 0;
                                    $ativo = $faixa === $chave;
                                @endphp

                                <button type="button" wire:click="filtrarPorFaixa('{{ $chave }}')"
                                        class="w-full text-left group rounded-md px-2 py-1.5 -mx-2 transition-colors {{ $ativo ? 'bg-gray-100' : 'hover:bg-gray-50' }}">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="flex items-center gap-2 text-xs font-semibold text-gray-700">
                                            <span class="w-2 h-2 rounded-full {{ $config['ponto'] }}"></span>
                                            {{ $config['rotulo'] }}
                                            <span class="font-normal text-gray-400">{{ $config['descricao'] }}</span>
                                        </span>
                                        <span class="text-xs font-bold text-gray-900 tabular-nums">{{ $qtd }}</span>
                                    </div>
                                    <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                        <div class="h-full {{ $config['ponto'] }} rounded-full transition-all" style="width: {{ $pct }}%"></div>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            @else
                <div class="px-6 py-10 text-center">
                    <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
                    </svg>
                    <p class="text-sm font-medium text-gray-600">Sem planejamento informado no período</p>
                    <p class="text-xs text-gray-500 mt-1 max-w-md mx-auto leading-relaxed">
                        O cálculo de faltas compara as sessões realizadas com o campo
                        <span class="font-semibold">Horas Planejadas</span> do cadastro de CH.
                        Nenhum dos {{ number_format($stats->registros, 0, ',', '.') }} registros deste filtro tem esse campo preenchido.
                    </p>
                </div>
            @endif
        </div>

        {{-- ══════════════ Filtros ══════════════ --}}
        <div class="bg-white shadow-sm sm:rounded-t-lg border border-gray-200 p-5 border-b-0">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-sm font-bold text-gray-800">Filtros</h3>
                <button type="button" wire:click="clearFilters" class="text-sm font-semibold text-red-500 hover:text-red-700 transition-colors">
                    Limpar filtros
                </button>
            </div>

            {{-- Quebra em mais estágios: com a sidebar de 16rem, o conteúdo útil é bem
                 menor que a viewport, então os cinco filtros só cabem lado a lado a
                 partir de lg. Antes disso vão de 2 em 2 / 3 em 3 em vez de empilhar. --}}
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 mb-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Unidade</label>
                    <select wire:model.live="unit_id" class="block w-full text-sm py-1.5 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todos</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->city ?? $unit->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Mês</label>
                    <select wire:model.live="month" class="block w-full text-sm py-1.5 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Selecione uma opção</option>
                        <option value="1">Janeiro</option>
                        <option value="2">Fevereiro</option>
                        <option value="3">Março</option>
                        <option value="4">Abril</option>
                        <option value="5">Maio</option>
                        <option value="6">Junho</option>
                        <option value="7">Julho</option>
                        <option value="8">Agosto</option>
                        <option value="9">Setembro</option>
                        <option value="10">Outubro</option>
                        <option value="11">Novembro</option>
                        <option value="12">Dezembro</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Ano</label>
                    <select wire:model.live="year" class="block w-full text-sm py-1.5 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Selecione uma opção</option>
                        @foreach($availableYears as $ano)
                            <option value="{{ $ano }}">{{ $ano }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Convênio</label>
                    <select wire:model.live="agreement_id" class="block w-full text-sm py-1.5 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todos</option>
                        @foreach($agreements as $convenio)
                            <option value="{{ $convenio->id }}">{{ $convenio->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Terapia</label>
                    <select wire:model.live="therapy_id" class="block w-full text-sm py-1.5 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todas</option>
                        @foreach($therapies as $terapia)
                            <option value="{{ $terapia->id }}">{{ $terapia->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- ══════════════ Tabela ══════════════ --}}
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-b-lg border border-gray-200">
            <div class="p-4 border-b border-gray-200 bg-gray-50 flex flex-wrap justify-between items-center gap-4">
                <div class="text-xs text-gray-500">
                    @if($faixa)
                        Exibindo faixa
                        <span class="font-semibold text-gray-800">
                            {{ $faixa === 'sem_plano' ? 'Sem planejamento informado' : ($faixas[$faixa]['rotulo'] ?? $faixa) }}
                        </span>
                        — {{ number_format($registros->total(), 0, ',', '.') }} registro(s)
                    @else
                        {{ number_format($registros->total(), 0, ',', '.') }} registro(s) no período
                    @endif
                </div>

                <div class="relative w-full sm:w-64">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Pesquisar..." class="block w-full pl-10 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse">
                    <thead>
                        <tr class="bg-white border-b border-gray-200 text-xs text-gray-600 font-bold whitespace-nowrap uppercase tracking-wide">
                            <th class="py-4 px-4 text-center w-8"><input type="checkbox" disabled class="rounded border-gray-300 text-blue-600 shadow-sm opacity-50"></th>
                            <th class="py-4 px-4">Nome</th>
                            <th class="py-4 px-4">Terapia</th>
                            <th class="py-4 px-4">Tipo de Atendimento</th>
                            <th class="py-4 px-4">Mês/Ano</th>
                            <th class="py-4 px-4">Requisição</th>
                            <th class="py-4 px-4 text-center">Sessões Solicitadas</th>
                            <th class="py-4 px-4 text-center">Sessões Liberadas</th>
                            <th class="py-4 px-4 text-center">
                                <span class="inline-flex items-center gap-1">
                                    Planejadas / mês
                                    <svg class="h-3.5 w-3.5 shrink-0 cursor-help text-gray-400 hover:text-blue-600"
                                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                                         role="img" aria-label="A Carga Horária Planejada é calculada a partir da agenda do paciente.">
                                        <title>A Carga Horária Planejada é calculada a partir da agenda do paciente.</title>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </span>
                            </th>
                            <th class="py-4 px-4 text-center">Realizadas</th>
                            <th class="py-4 px-4 text-center bg-gray-50">Aderência</th>
                            <th class="py-4 px-4 text-center bg-gray-50">Falta (sessões)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-800">
                        @forelse ($registros as $registro)
                            @php
                                $aderencia      = $this->aderenciaDaLinha($registro);
                                $faixaLinha     = $this->faixaDaLinha($registro);
                                $planejadaLinha = $this->planejadasNoMes($registro);
                                $faltaLinha     = $this->faltaDaLinha($registro);

                                // Reaproveita as classes definidas em Index::FAIXAS, para que a cor
                                // do badge nunca divirja da cor usada no painel de distribuição.
                                $badge = $faixas[$faixaLinha]['badge'] ?? 'bg-gray-50 text-gray-400 border-gray-200';
                            @endphp

                            <tr wire:key="registro-{{ $registro->id }}" class="hover:bg-gray-50 transition-colors whitespace-nowrap">
                                <td class="py-4 px-4 text-center"><input type="checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm"></td>
                                <td class="py-4 px-4 font-medium text-xs uppercase">{{ $registro->patient->name ?? '-' }}</td>
                                <td class="py-4 px-4 text-xs uppercase">{{ $registro->therapy->name ?? '-' }}</td>
                                <td class="py-4 px-4 text-xs">{{ $registro->serviceType->name ?? 'Clínica' }}</td>
                                <td class="py-4 px-4 text-xs">
                                    {{ $registro->month_year ? \Carbon\Carbon::parse($registro->month_year)->translatedFormat('F \d\e Y') : '-' }}
                                </td>
                                <td class="py-4 px-4 text-xs">{{ $registro->requisition_number ?? '-' }}</td>
                                <td class="py-4 px-4 text-center font-semibold">{{ number_format($registro->requested_hours ?? 0, 0, ',', '.') }}</td>
                                <td class="py-4 px-4 text-center font-semibold">{{ number_format($registro->approved_hours ?? 0, 0, ',', '.') }}</td>

                                {{-- Planejadas: mostra o equivalente mensal (que é o que entra na
                                     conta da falta) e, abaixo, o valor semanal como foi cadastrado. --}}
                                <td class="py-4 px-4 text-center">
                                    @if($planejadaLinha > 0)
                                        <span class="font-semibold">{{ number_format($planejadaLinha, 0, ',', '.') }}</span>
                                        <span class="block text-[10px] text-gray-400 font-normal">{{ rtrim(rtrim($registro->planned_hours, '0'), '.') }}/semana</span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>

                                {{-- Realizadas: sessões como número principal; duração e contagem
                                     de atendimentos ficam como contexto secundário. --}}
                                <td class="py-4 px-4 text-center">
                                    <span class="font-bold text-purple-600">{{ number_format($registro->realized_sessions ?? 0, 0, ',', '.') }}</span>
                                    @if(($registro->realized_appointments ?? 0) > 0)
                                        <span class="block text-[10px] text-gray-400 font-normal">
                                            {{ $registro->realized_appointments }} atend. · {{ $this->formatTime($registro->realized_hours ?? 0) }}
                                        </span>
                                    @endif
                                </td>

                                {{-- Aderência --}}
                                <td class="py-4 px-4 text-center bg-gray-50/50">
                                    @if($aderencia !== null)
                                        <span class="inline-flex items-center rounded border px-2 py-0.5 text-xs font-bold tabular-nums {{ $badge }}">
                                            {{ number_format($aderencia, 0, ',', '.') }}%
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-300" title="Sem carga horária planejada informada">—</span>
                                    @endif
                                </td>

                                {{-- Falta em sessões --}}
                                <td class="py-4 px-4 text-center bg-gray-50/50">
                                    @if($faltaLinha === null)
                                        <span class="text-xs text-gray-300">—</span>
                                    @elseif($faltaLinha > 0)
                                        <span class="text-sm font-bold text-red-600 tabular-nums">{{ number_format($faltaLinha, 0, ',', '.') }}</span>
                                    @else
                                        <span class="text-xs font-semibold text-green-600">Em dia</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                {{-- colspan corrigido: eram 10 colunas com colspan=9, agora são 12 --}}
                                <td colspan="12" class="py-12">
                                    <div class="flex flex-col items-center justify-center text-gray-500 w-full">
                                        <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                        </svg>
                                        <p class="text-sm">Nenhum registro de carga horária encontrado.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="py-3 px-4 border-t border-gray-200">
                {{ $registros->links() }}
            </div>
        </div>
    </div>
</div>
