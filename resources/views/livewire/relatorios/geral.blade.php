@php
    $mesesPorExtenso = [1=>'Janeiro',2=>'Fevereiro',3=>'Março',4=>'Abril',5=>'Maio',6=>'Junho',
        7=>'Julho',8=>'Agosto',9=>'Setembro',10=>'Outubro',11=>'Novembro',12=>'Dezembro'];
    $rotuloMes = ($mesesPorExtenso[(int) $mes] ?? '') . ' de ' . $ano;

    // Paleta categórica validada (CVD ΔE 9.1 no pior par adjacente). Usada só onde há mais
    // de uma série no mesmo gráfico; ranking de série única leva uma cor só, porque ali a
    // categoria já está no eixo e cor por barra não codifica nada.
    $serie = ['#2a78d6','#eb6834','#1baf7a','#eda100','#e87ba4','#008300','#4a3aa7','#e34948'];
@endphp

<div class="nd-relatorios">

    <style>
        .nd-relatorios {
            --ink:      #111a26;
            --ink-2:    #55616f;
            --ink-3:    #8d98a6;
            --line:     #e4e9ef;
            --surface:  #ffffff;
            --accent:   #2a78d6;
            --accent-3: #eef4fc;
        }
        .nd-relatorios .nd-num { font-variant-numeric: tabular-nums; letter-spacing: -0.01em; }
        .nd-relatorios .nd-eyebrow {
            font-size: 11px; font-weight: 700; letter-spacing: 0.09em;
            text-transform: uppercase; color: var(--ink-3);
        }
        .nd-relatorios .nd-display {
            font-weight: 800; letter-spacing: -0.035em; line-height: 0.95;
            font-variant-numeric: tabular-nums; color: var(--ink);
        }
        .nd-relatorios .nd-card {
            background: var(--surface); border: 1px solid var(--line); border-radius: 14px;
        }
        .nd-relatorios .nd-title { font-weight: 700; letter-spacing: -0.011em; color: var(--ink); }
        /* Barras da fita: a transição só existe para quem não pediu menos movimento. */
        .nd-relatorios .nd-fita-barra { transition: background-color .15s ease; }
        @media (prefers-reduced-motion: reduce) {
            .nd-relatorios * { transition: none !important; animation: none !important; }
        }
        .nd-relatorios :focus-visible {
            outline: 2px solid var(--accent); outline-offset: 2px; border-radius: 6px;
        }
    </style>

    <div class="mb-7 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="nd-eyebrow">Relatórios</p>
            <h2 class="mt-1 text-[28px] leading-none nd-display">Atendimentos de {{ $rotuloMes }}</h2>
            <p class="mt-2 text-sm" style="color: var(--ink-2)">
                Volume, distribuição e ritmo do período selecionado.
            </p>
        </div>

        <button wire:click="exportarPDF" wire:loading.attr="disabled" wire:target="exportarPDF"
                class="inline-flex items-center gap-2 rounded-lg border px-4 py-2.5 text-sm font-semibold transition-colors hover:bg-gray-50 disabled:opacity-60"
                style="border-color: var(--line); color: var(--ink)">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span wire:loading.remove wire:target="exportarPDF">Baixar PDF</span>
            <span wire:loading wire:target="exportarPDF">Gerando…</span>
        </button>
    </div>

    <div class="mb-6 -mx-4 overflow-x-auto px-4 sm:mx-0 sm:flex sm:justify-center sm:px-0">
        <div class="inline-flex rounded-lg border bg-white p-1" style="border-color: var(--line)">
            <button wire:click="$set('viewMode', 'geral')" class="px-6 py-2 rounded-md font-semibold text-sm flex items-center gap-2 transition-colors {{ $viewMode === 'geral' ? 'bg-[#eef4fc] text-[#2a78d6]' : 'text-gray-500 hover:text-gray-800' }}">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"></path></svg>
                <span class="sm:hidden">Geral</span>
                <span class="hidden sm:inline">Relatório Geral</span>
            </button>
            <button wire:click="$set('viewMode', 'comparativo')" class="px-6 py-2 rounded-md font-semibold text-sm flex items-center gap-2 transition-colors {{ $viewMode === 'comparativo' ? 'bg-[#eef4fc] text-[#2a78d6]' : 'text-gray-500 hover:text-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path></svg>
                <span class="sm:hidden">Dia a dia</span>
                <span class="hidden sm:inline">Comparativo Dia x Dia</span>
            </button>
            <button wire:click="$set('viewMode', 'ocupacao')" class="px-6 py-2 rounded-md font-semibold text-sm flex items-center gap-2 transition-colors {{ $viewMode === 'ocupacao' ? 'bg-[#eef4fc] text-[#2a78d6]' : 'text-gray-500 hover:text-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span class="sm:hidden">Ocupação</span>
                <span class="hidden sm:inline">Frequência e Ocupação</span>
            </button>
        </div>
    </div>

    <div class="nd-card mb-6">
        <div class="border-b p-5" style="border-color: var(--line)">
            <h3 class="nd-title text-[15px]">Filtros</h3>
            <p class="mt-0.5 text-xs" style="color: var(--ink-3)">
                Valem para as três abas. O mês e o ano recortam todo o período analisado.
            </p>
        </div>
        <div class="grid grid-cols-1 gap-5 p-5 md:grid-cols-3">
            <div>
                <label class="nd-eyebrow mb-1.5 block">Mês</label>
                <select wire:model="mes" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="1">Janeiro</option><option value="2">Fevereiro</option><option value="3">Março</option><option value="4">Abril</option><option value="5">Maio</option><option value="6">Junho</option><option value="7">Julho</option><option value="8">Agosto</option><option value="9">Setembro</option><option value="10">Outubro</option><option value="11">Novembro</option><option value="12">Dezembro</option>
                </select>
            </div>
            <div>
                <label class="nd-eyebrow mb-1.5 block">Ano</label>
                <select wire:model="ano" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @foreach($anosDisponiveis as $a) <option value="{{ $a }}">{{ $a }}</option> @endforeach
                </select>
            </div>
            <div>
                <label class="nd-eyebrow mb-1.5 block">Convênio</label>
                <select wire:model="convenio_id" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Todos os convênios</option>
                    @foreach($convenios as $c) <option value="{{ $c->id }}">{{ $c->name }}</option> @endforeach
                </select>
            </div>
            <div>
                <label class="nd-eyebrow mb-1.5 block">Paciente</label>
                <select wire:model="paciente_id" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Todos os pacientes</option>
                    @foreach($pacientes as $p) <option value="{{ $p->id }}">{{ $p->name }}</option> @endforeach
                </select>
            </div>
            <div>
                <label class="nd-eyebrow mb-1.5 block">Terapia</label>
                <select wire:model="terapia_id" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Todas as terapias</option>
                    @foreach($terapias as $t) <option value="{{ $t->id }}">{{ $t->name }}</option> @endforeach
                </select>
            </div>
            <div>
                <label class="nd-eyebrow mb-1.5 block">Unidade(s)</label>
                <select wire:model="unidade_id" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Todas as unidades</option>
                    @foreach($unidades as $u) <option value="{{ $u->id }}">{{ $u->city ?? $u->name }}</option> @endforeach
                </select>
            </div>
        </div>
        <div class="flex justify-end gap-2 rounded-b-[14px] border-t p-4" style="border-color: var(--line); background: #fafbfc">
            <button wire:click="limparFiltros" class="rounded-lg px-4 py-2 text-sm font-semibold transition-colors hover:bg-gray-100" style="color: var(--ink-2)">Limpar filtros</button>
            <button wire:click="aplicarFiltros" class="flex items-center gap-2 rounded-lg px-5 py-2 text-sm font-semibold text-white transition-colors hover:opacity-90" style="background: var(--accent)">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd"></path></svg>
                Aplicar Filtros
            </button>
        </div>
    </div>

    @if($viewMode === 'geral')

        {{-- A fita do mês: cada dia é uma barra, o fim de semana fica entalhado. É o gráfico
             diário e o número de destaque no mesmo lugar, em vez de quatro caixas soltas
             mais um gráfico repetindo a mesma série. --}}
        <div class="nd-card mb-6 p-6">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-end">

                <div class="shrink-0">
                    <p class="nd-eyebrow">Sessões no mês</p>
                    <p class="mt-1 text-[52px] nd-display">{{ number_format($totalSessoes, 0, ',', '.') }}</p>
                    <p class="mt-1.5 text-sm" style="color: var(--ink-2)">
                        média de <span class="font-semibold nd-num" style="color: var(--ink)">{{ number_format($mediaDiaria, 0, ',', '.') }}</span> por dia com atendimento
                    </p>
                </div>

                <div class="min-w-0 flex-1">
                    {{-- h-32 (não h-28): a barra em si ainda escala para no máximo 100px
                         (ver picoDoMes no componente), sobrando 20px fixos no topo para o
                         número não colidir com a barra mais alta do mês. --}}
                    <div class="flex h-32 items-end gap-[3px]">
                        @foreach($fitaDoMes as $d)
                            <div class="group flex min-w-0 flex-1 flex-col items-center justify-end"
                                 title="{{ $d['titulo'] }}: {{ number_format($d['total'], 0, ',', '.') }} sessões">
                                @if($d['total'] > 0)
                                    <span class="mb-1 whitespace-nowrap text-[9px] font-semibold leading-none nd-num"
                                          style="color: {{ $d['fimDeSemana'] ? 'var(--ink-3)' : 'var(--ink-2)' }}">
                                        {{ number_format($d['total'], 0, ',', '.') }}
                                    </span>
                                @endif
                                <div class="nd-fita-barra w-full rounded-[2px]"
                                     style="height: {{ $d['total'] > 0 ? max(3, (int) round($d['total'] / $picoDoMes * 100)) : 3 }}px;
                                            background: {{ $d['total'] > 0 ? 'var(--accent)' : 'var(--line)' }};
                                            opacity: {{ $d['fimDeSemana'] ? '0.35' : '1' }}"></div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-1.5 hidden gap-[3px] sm:flex">
                        @foreach($fitaDoMes as $d)
                            <span class="min-w-0 flex-1 text-center text-[9px] font-semibold nd-num"
                                  style="color: {{ $d['fimDeSemana'] ? 'var(--ink-3)' : 'var(--ink-2)' }}">
                                {{ $d['inicial'] }}
                            </span>
                        @endforeach
                    </div>
                    <p class="mt-2.5 text-xs" style="color: var(--ink-3)">
                        Cada barra é um dia. Vazios são fim de semana, feriado ou dia sem lançamento.
                    </p>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-2 gap-px border-t pt-5 sm:grid-cols-3" style="border-color: var(--line)">
                <div>
                    <p class="nd-eyebrow">Atendimentos</p>
                    <p class="mt-1 text-xl font-bold nd-num" style="color: var(--ink)">{{ number_format($totalAtendimentos, 0, ',', '.') }}</p>
                    <p class="mt-0.5 text-xs" style="color: var(--ink-3)">registros lançados</p>
                </div>
                <div>
                    <p class="nd-eyebrow">Beneficiários</p>
                    <p class="mt-1 text-xl font-bold nd-num" style="color: var(--ink)">{{ number_format($beneficiariosAtendidos, 0, ',', '.') }}</p>
                    <p class="mt-0.5 text-xs" style="color: var(--ink-3)">pacientes distintos</p>
                </div>
                <div>
                    <p class="nd-eyebrow">Sessões por atendimento</p>
                    <p class="mt-1 text-xl font-bold nd-num" style="color: var(--ink)">
                        {{ $totalAtendimentos > 0 ? number_format($totalSessoes / $totalAtendimentos, 2, ',', '.') : '—' }}
                    </p>
                    <p class="mt-0.5 text-xs" style="color: var(--ink-3)">média do período</p>
                </div>
            </div>
        </div>

        <div wire:key="charts-geral-{{ $mes }}-{{ $ano }}-{{ $unidade_id }}-{{ $terapia_id }}-{{ $convenio_id }}" class="space-y-6">
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                <div class="nd-card p-5">
                    <h3 class="nd-title mb-4 text-[15px]">Sessões por Terapia</h3>
                    <div x-data="{
                        init() {
                            let rawData = @js($graficoTerapias);
                            let options = {
                                chart: { type: 'bar', height: 350, toolbar: { show: false }, fontFamily: 'inherit' },
                                series: [{ name: 'Sessões', data: rawData.map(d => d.total) }],
                                xaxis: { categories: rawData.map(d => d.nome), labels: { style: { colors: '#55616f' } } },
                                colors: ['#2a78d6'],
                                plotOptions: {
                                    bar: {
                                        horizontal: true,
                                        borderRadius: 3,
                                        dataLabels: { position: 'top' }
                                    }
                                },
                                dataLabels: {
                                    enabled: true,
                                    textAnchor: 'start',
                                    offsetX: 5,
                                    dropShadow: { enabled: false },
                                    style: { fontSize: '12px', colors: ['#111a26'], fontWeight: '700' }
                                },
                                legend: { show: false },
                                grid: { borderColor: '#eef1f5', strokeDashArray: 4, xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } }
                            };
                            new ApexCharts(this.$refs.chartTerapias, options).render();
                        }
                    }">
                        <div x-ref="chartTerapias"></div>
                    </div>
                </div>

                <div class="nd-card p-5">
                    <h3 class="nd-title mb-4 text-[15px]">Sessões por Unidade</h3>
                    <div x-data="{
                        init() {
                            let rawData = @js($graficoUnidades);
                            let options = {
                                chart: { type: 'bar', height: 350, toolbar: { show: false }, fontFamily: 'inherit' },
                                series: [{ name: 'Sessões', data: rawData.map(d => d.total) }],
                                xaxis: { categories: rawData.map(d => d.nome), labels: { style: { colors: '#55616f' } } },
                                colors: ['#2a78d6'],
                                plotOptions: {
                                    bar: {
                                        horizontal: true,
                                        borderRadius: 3,
                                        dataLabels: { position: 'top' }
                                    }
                                },
                                dataLabels: {
                                    enabled: true,
                                    textAnchor: 'start',
                                    offsetX: 5,
                                    dropShadow: { enabled: false },
                                    style: { fontSize: '12px', colors: ['#111a26'], fontWeight: '700' }
                                },
                                legend: { show: false },
                                grid: { borderColor: '#eef1f5', strokeDashArray: 4, xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } }
                            };
                            new ApexCharts(this.$refs.chartUnidades, options).render();
                        }
                    }">
                        <div x-ref="chartUnidades"></div>
                    </div>
                </div>

                <div class="nd-card p-5">
                    <h3 class="nd-title mb-4 text-[15px]">Beneficiários Atendidos por Unidade</h3>
                    <div x-data="{
                        init() {
                            let rawData = @js($graficoBeneficiariosUnidade);
                            let options = {
                                chart: { type: 'bar', height: 250, toolbar: { show: false }, fontFamily: 'inherit' },
                                series: [{ name: 'Pacientes Únicos', data: rawData.map(d => d.total) }],
                                xaxis: { categories: rawData.map(d => d.nome), labels: { style: { colors: '#55616f' } } },
                                colors: ['#2a78d6'],
                                plotOptions: {
                                    bar: {
                                        horizontal: true,
                                        borderRadius: 3,
                                        dataLabels: { position: 'top' }
                                    }
                                },
                                dataLabels: {
                                    enabled: true,
                                    textAnchor: 'start',
                                    offsetX: 5,
                                    dropShadow: { enabled: false },
                                    style: { fontSize: '12px', colors: ['#111a26'], fontWeight: '700' }
                                },
                                legend: { show: false },
                                grid: { borderColor: '#eef1f5', strokeDashArray: 4, xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } }
                            };
                            new ApexCharts(this.$refs.chartBeneficiarios, options).render();
                        }
                    }">
                        <div x-ref="chartBeneficiarios"></div>
                    </div>
                </div>

                <div class="nd-card p-5">
                    <h3 class="nd-title mb-4 text-[15px]">Sessões por Convênio</h3>
                    <div x-data="{
                        init() {
                            let rawData = @js($graficoConvenios);
                            let options = {
                                chart: { type: 'bar', height: 250, toolbar: { show: false }, fontFamily: 'inherit' },
                                series: [{ name: 'Sessões', data: rawData.map(d => d.total) }],
                                xaxis: { categories: rawData.map(d => d.nome), labels: { style: { colors: '#55616f' } } },
                                colors: ['#2a78d6'],
                                plotOptions: {
                                    bar: {
                                        horizontal: true,
                                        borderRadius: 3,
                                        dataLabels: { position: 'top' }
                                    }
                                },
                                dataLabels: {
                                    enabled: true,
                                    textAnchor: 'start',
                                    offsetX: 5,
                                    dropShadow: { enabled: false },
                                    style: { fontSize: '12px', colors: ['#111a26'], fontWeight: '700' }
                                },
                                legend: { show: false },
                                grid: { borderColor: '#eef1f5', strokeDashArray: 4, xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } }
                            };
                            new ApexCharts(this.$refs.chartConvenios, options).render();
                        }
                    }">
                        <div x-ref="chartConvenios"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-200 mb-8 overflow-hidden">
            <div class="p-4 border-b border-gray-100 flex justify-end">
                <div class="relative w-full sm:w-64">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Pesquisar paciente..." class="block w-full pl-10 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-xs text-gray-600 font-bold uppercase tracking-wide">
                            <th class="py-3 px-4">Mês</th>
                            <th class="py-3 px-4">Paciente</th>
                            <th class="py-3 px-4">Terapia</th>
                            <th class="py-3 px-4 text-center">Total de Sessões</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-800">
                        @forelse ($tabelaResumo as $linha)
                            <tr class="hover:bg-gray-50">
                                <td class="py-3 px-4 text-xs font-medium">{{ str_pad($mes, 2, '0', STR_PAD_LEFT) }}/{{ $ano }}</td>
                                <td class="py-3 px-4 font-medium uppercase text-xs">{{ $linha->patient->name ?? '-' }}</td>
                                <td class="py-3 px-4 uppercase text-xs">{{ $linha->therapy->name ?? '-' }}</td>
                                <td class="py-3 px-4 text-center font-bold text-blue-600">{{ $linha->total_sessoes }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-6 text-center text-gray-500 text-sm">Nenhum registro encontrado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="py-3 px-4 border-t border-gray-200 bg-gray-50">
                {{ $tabelaResumo->links() }}
            </div>
        </div>

    @elseif($viewMode === 'comparativo')

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 mt-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <p class="text-xs font-semibold text-gray-500 mb-2 flex items-center gap-2"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg> Dia + Movimentado (Mês Selecionado)</p>
                <p class="text-3xl font-bold text-gray-900 mb-2">{{ $melhorDiaAtual }}</p>
                <p class="text-sm text-green-600 font-medium">{{ number_format($totalMelhorDiaAtual, 0, ',', '.') }} sessões no total</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <p class="text-xs font-semibold text-gray-500 mb-2 flex items-center gap-2"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg> Dia + Movimentado (Mês Anterior)</p>
                <p class="text-3xl font-bold text-gray-900 mb-2">{{ $melhorDiaAnterior }}</p>
                <p class="text-sm text-gray-500 font-medium">{{ number_format($totalMelhorDiaAnterior, 0, ',', '.') }} sessões no total</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <p class="text-xs font-semibold text-gray-500 mb-2 flex items-center gap-2"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg> Média Diária</p>
                <p class="text-3xl font-bold text-gray-900 mb-2">{{ $mediaDiaria }} /dia</p>
                <p class="text-sm text-blue-500 font-medium">Média por dia da semana</p>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 mb-8" wire:key="line-chart-{{ $mes }}-{{ $ano }}">
            <h3 class="nd-title mb-6 text-[17px]">Comparativo: Mês Selecionado vs Mês Anterior</h3>
            <div x-data="{
                init() {
                    let options = {
                        chart: { type: 'line', height: 350, toolbar: { show: false }, fontFamily: 'inherit' },
                        series: [
                            { name: 'Mês Atual', data: @js($linhaAtual) },
                            { name: 'Mês Anterior', data: @js($linhaAnterior) }
                        ],
                        xaxis: { categories: @js($diasLabels), labels: { style: { colors: '#55616f' } } },
                        colors: ['#2a78d6', '#b6c0cc'],
                        stroke: { width: [4, 3], curve: 'smooth', dashArray: [0, 5] },
                        markers: { size: 6, hover: { size: 8 } },
                        dataLabels: { enabled: false },
                        legend: { position: 'bottom' },
                        grid: { borderColor: '#eef1f5', strokeDashArray: 4 }
                    };
                    new ApexCharts(this.$refs.chartLine, options).render();
                }
            }">
                <div x-ref="chartLine"></div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100" wire:key="bar-chart-{{ $mes }}-{{ $ano }}">
            <h3 class="nd-title mb-6 text-[17px]">Terapias por Dia da Semana (Mês Selecionado)</h3>
            <div x-data="{
                init() {
                    let options = {
                        chart: { type: 'bar', height: 400, stacked: false, toolbar: { show: false }, fontFamily: 'inherit' },
                        series: @js($graficoTerapiasSemana),
                        xaxis: { categories: ['Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'] },
                        colors: ['#2a78d6','#eb6834','#1baf7a','#eda100','#e87ba4','#008300','#4a3aa7','#e34948'],
                        plotOptions: { 
                            bar: { 
                                borderRadius: 2, 
                                columnWidth: '70%',
                                dataLabels: { position: 'top' } // Diz para colocar o número no topo
                            } 
                        },
                        dataLabels: { 
                            enabled: true, // Ativa os números
                            offsetY: -20, // Empurra para fora da barra
                            style: { fontSize: '10px', colors: ['#111a26'], fontWeight: '700' },
                            formatter: function (val) {
                                return val > 0 ? val : ''; // Só mostra o número se for maior que zero para não poluir o gráfico
                            }
                        },
                        stroke: { show: true, width: 2, colors: ['transparent'] },
                        legend: { position: 'bottom' },
                        grid: { borderColor: '#eef1f5', strokeDashArray: 4 }
                    };
                    new ApexCharts(this.$refs.chartWeek, options).render();
                }
            }">
                <div x-ref="chartWeek"></div>
            </div>
        </div>

    @elseif($viewMode === 'ocupacao')

        <div wire:key="ocupacao-{{ $mes }}-{{ $ano }}-{{ $unidade_id }}-{{ $terapia_id }}-{{ $convenio_id }}" class="space-y-6">

            <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                <p class="text-sm leading-relaxed text-blue-900">
                    Cada sessão entra pelo <strong>horário do check-in</strong> do atendimento — manhã
                    antes das 12h, tarde a partir dela. Os percentuais mostram onde a demanda se
                    concentra, não quanto da capacidade foi usada.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="nd-card p-5">
                    <h3 class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-500">Sessões</h3>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($ocupTotal, 0, ',', '.') }}</p>
                    <p class="mt-2 text-xs text-gray-400">
                        média de {{ number_format($ocupMedia, 0, ',', '.') }} por dia com atendimento
                    </p>
                </div>

                <div class="nd-card p-5">
                    <h3 class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-500">Dia Mais Cheio</h3>
                    <p class="text-2xl font-bold text-blue-600">{{ $ocupPicoDia['dia'] ?? '—' }}</p>
                    <p class="mt-2 text-xs text-gray-400">
                        {{ number_format($ocupPicoDia['percentual'] ?? 0, 1, ',', '.') }}% das sessões
                    </p>
                </div>

                <div class="nd-card p-5">
                    <h3 class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-500">Horário de Pico</h3>
                    <p class="text-2xl font-bold text-violet-600">{{ $ocupPicoHora['rotulo'] ?? '—' }}</p>
                    <p class="mt-2 text-xs text-gray-400">
                        {{ number_format($ocupPicoHora['percentual'] ?? 0, 1, ',', '.') }}% das sessões
                    </p>
                </div>

                <div class="nd-card p-5">
                    <h3 class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-500">Manhã x Tarde</h3>
                    <p class="text-2xl font-bold text-gray-900">
                        {{ number_format($ocupPctManha, 0, ',', '.') }}<span class="text-gray-300">/</span>{{ number_format($ocupPctTarde, 0, ',', '.') }}
                    </p>
                    <div class="mt-2 flex h-2 w-full overflow-hidden rounded-full bg-gray-100">
                        <div class="bg-sky-400" style="width: {{ $ocupPctManha }}%"></div>
                        <div class="bg-rose-400" style="width: {{ $ocupPctTarde }}%"></div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                <div class="nd-card p-5">
                    <h3 class="nd-title mb-1 text-[15px]">Ocupação por Dia da Semana</h3>
                    <p class="mb-4 text-xs text-gray-500">Qual dia concentra mais sessões.</p>
                    <div x-data="{
                        init() {
                            let dados = @js($ocupPorDia);
                            new ApexCharts(this.$refs.chartDiaSemana, {
                                chart: { type: 'bar', height: 300, toolbar: { show: false }, fontFamily: 'inherit' },
                                series: [{ name: 'Sessões', data: dados.map(d => d.total) }],
                                xaxis: { categories: dados.map(d => d.dia), labels: { style: { colors: '#55616f' } } },
                                yaxis: { labels: { style: { colors: '#55616f' } } },
                                colors: ['#2a78d6'],
                                plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
                                dataLabels: {
                                    enabled: true, offsetY: -20,
                                    formatter: (v, o) => dados[o.dataPointIndex].percentual.toFixed(1).replace('.', ',') + '%',
                                    style: { fontSize: '11px', colors: ['#111a26'], fontWeight: '700' }
                                },
                                tooltip: { y: { formatter: v => v.toLocaleString('pt-BR') + ' sessões' } },
                                grid: { borderColor: '#eef1f5', strokeDashArray: 4 }
                            }).render();
                        }
                    }">
                        <div x-ref="chartDiaSemana"></div>
                    </div>
                </div>

                <div class="nd-card p-5">
                    <h3 class="nd-title mb-1 text-[15px]">Ocupação por Turno</h3>
                    <p class="mb-4 text-xs text-gray-500">Divisão de cada dia entre manhã e tarde.</p>
                    <div x-data="{
                        init() {
                            let dados = @js($ocupPorDia);
                            new ApexCharts(this.$refs.chartTurno, {
                                chart: { type: 'bar', height: 300, stacked: true, stackType: '100%', toolbar: { show: false }, fontFamily: 'inherit' },
                                series: [
                                    { name: 'Manhã', data: dados.map(d => d.manha) },
                                    { name: 'Tarde', data: dados.map(d => d.tarde) }
                                ],
                                xaxis: { categories: dados.map(d => d.dia), labels: { style: { colors: '#55616f' } } },
                                yaxis: { labels: { style: { colors: '#55616f' } } },
                                colors: ['#2a78d6', '#eb6834'],
                                plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '65%' } },
                                dataLabels: { enabled: true, formatter: v => v.toFixed(0) + '%', style: { fontSize: '11px', fontWeight: 'bold' } },
                                legend: { position: 'bottom', markers: { radius: 3 } },
                                tooltip: { y: { formatter: v => v.toLocaleString('pt-BR') + ' sessões' } },
                                grid: { borderColor: '#eef1f5', strokeDashArray: 4 }
                            }).render();
                        }
                    }">
                        <div x-ref="chartTurno"></div>
                    </div>
                </div>
            </div>

            <div class="nd-card p-5">
                <h3 class="nd-title mb-1 text-[15px]">Ocupação por Hora</h3>
                <p class="mb-4 text-xs text-gray-500">Participação de cada horário no total do período.</p>
                <div x-data="{
                    init() {
                        let dados = @js($ocupPorHora);
                        new ApexCharts(this.$refs.chartHora, {
                            chart: { type: 'bar', height: 340, toolbar: { show: false }, fontFamily: 'inherit' },
                            series: [{ name: 'Sessões', data: dados.map(d => d.total) }],
                            xaxis: { categories: dados.map(d => d.rotulo), labels: { style: { colors: '#55616f' } } },
                            yaxis: { labels: { style: { colors: '#55616f' } } },
                            colors: ['#2a78d6'],
                            plotOptions: { bar: { borderRadius: 3, columnWidth: '65%' } },
                            dataLabels: {
                                enabled: true, offsetY: -20,
                                formatter: (v, o) => dados[o.dataPointIndex].percentual.toFixed(1).replace('.', ',') + '%',
                                style: { fontSize: '10px', colors: ['#111a26'], fontWeight: '700' }
                            },
                            tooltip: { y: { formatter: v => v.toLocaleString('pt-BR') + ' sessões' } },
                            grid: { borderColor: '#eef1f5', strokeDashArray: 4 }
                        }).render();
                    }
                }">
                    <div x-ref="chartHora"></div>
                </div>
            </div>

            <div class="nd-card p-5">
                <h3 class="nd-title mb-1 text-[15px]">Horário de Pico por Dia da Semana</h3>
                <p class="mb-4 text-xs text-gray-500">
                    Quanto mais escuro, mais sessões naquele cruzamento. É o painel que
                    responde onde alocar profissional.
                </p>
                <div x-data="{
                    init() {
                        new ApexCharts(this.$refs.chartMapa, {
                            chart: { type: 'heatmap', height: 320, toolbar: { show: false }, fontFamily: 'inherit' },
                            series: @js($ocupMapaCalor),
                            colors: ['#2a78d6'],
                            {{-- Sem "colors" aqui, o ApexCharts usa branco para todo dataLabel de
                                 heatmap — ilegível nas células claras (baixa contagem), que são a
                                 maioria da grade. #111a26 (--ink) fica visível em toda a escala. --}}
                            dataLabels: { enabled: true, style: { fontSize: '10px', fontWeight: 'bold', colors: ['#111a26'] } },
                            xaxis: { labels: { style: { colors: '#55616f' } } },
                            yaxis: { labels: { style: { colors: '#55616f' } } },
                            plotOptions: { heatmap: { radius: 4, enableShades: true, shadeIntensity: 0.6 } },
                            tooltip: { y: { formatter: v => v.toLocaleString('pt-BR') + ' sessões' } },
                            grid: { borderColor: '#eef1f5' }
                        }).render();
                    }
                }">
                    <div x-ref="chartMapa"></div>
                </div>
            </div>
        </div>

    @endif

</div>