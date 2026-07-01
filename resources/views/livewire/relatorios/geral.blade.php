<div>
    <div class="flex justify-between items-center mb-6">
        <div>
            <nav class="flex text-xs text-gray-500 mb-1">
                <ol class="inline-flex items-center space-x-1">
                    <li><span>Dashboard</span></li>
                    <li><span class="mx-1">/</span></li>
                    <li class="text-gray-700 font-medium">Relatórios de Atendimento</li>
                </ol>
            </nav>
            <h2 class="font-bold text-2xl text-gray-900">Relatórios de Atendimento</h2>
        </div>
        
        <button wire:click="exportarPDF" class="px-4 py-2 bg-red-600 text-white text-sm font-semibold rounded-md hover:bg-red-700 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            Exportar para PDF
        </button>
    </div>

    <div class="flex justify-center mb-6">
        <div class="inline-flex bg-white border border-gray-200 rounded-lg p-1 shadow-sm">
            <button wire:click="$set('viewMode', 'geral')" class="px-6 py-2 rounded-md font-semibold text-sm flex items-center gap-2 transition-colors {{ $viewMode === 'geral' ? 'bg-blue-50 text-blue-600' : 'text-gray-500 hover:text-gray-700' }}">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"></path></svg>
                Relatório Geral
            </button>
            <button wire:click="$set('viewMode', 'comparativo')" class="px-6 py-2 rounded-md font-semibold text-sm flex items-center gap-2 transition-colors {{ $viewMode === 'comparativo' ? 'bg-blue-50 text-blue-600' : 'text-gray-500 hover:text-gray-700' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path></svg>
                Comparativo Dia x Dia
            </button>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
        <div class="p-5 border-b border-gray-100">
            <h3 class="font-bold text-gray-800">Filtros Gerenciais</h3>
        </div>
        <div class="p-5 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Mês</label>
                <select wire:model="mes" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="1">Janeiro</option><option value="2">Fevereiro</option><option value="3">Março</option><option value="4">Abril</option><option value="5">Maio</option><option value="6">Junho</option><option value="7">Julho</option><option value="8">Agosto</option><option value="9">Setembro</option><option value="10">Outubro</option><option value="11">Novembro</option><option value="12">Dezembro</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Ano</label>
                <select wire:model="ano" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                    @foreach($anosDisponiveis as $a) <option value="{{ $a }}">{{ $a }}</option> @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Convênio</label>
                <select wire:model="convenio_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="">Todos os convênios</option>
                    @foreach($convenios as $c) <option value="{{ $c->id }}">{{ $c->name }}</option> @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Paciente</label>
                <select wire:model="paciente_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="">Todos os pacientes</option>
                    @foreach($pacientes as $p) <option value="{{ $p->id }}">{{ $p->name }}</option> @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Terapia</label>
                <select wire:model="terapia_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="">Todas as terapias</option>
                    @foreach($terapias as $t) <option value="{{ $t->id }}">{{ $t->name }}</option> @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Unidade(s)</label>
                <select wire:model="unidade_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="">Todas as unidades</option>
                    @foreach($unidades as $u) <option value="{{ $u->id }}">{{ $u->city ?? $u->name }}</option> @endforeach
                </select>
            </div>
        </div>
        <div class="bg-gray-50 p-4 border-t border-gray-100 flex justify-end gap-2 rounded-b-xl">
            <button wire:click="limparFiltros" class="px-4 py-2 text-gray-600 font-semibold text-sm hover:bg-gray-200 rounded-md transition-colors">Limpar</button>
            <button wire:click="aplicarFiltros" class="px-5 py-2 bg-blue-500 text-white font-semibold text-sm rounded-md hover:bg-blue-600 transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd"></path></svg>
                Aplicar Filtros
            </button>
        </div>
    </div>

    @if($viewMode === 'geral')

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <p class="text-xs font-semibold text-gray-500 mb-1">Total de Sessões</p>
                <p class="text-3xl font-bold text-gray-900 mb-2">{{ number_format($totalSessoes, 0, ',', '.') }}</p>
                <p class="text-xs text-blue-600 font-medium flex items-center gap-1">Soma das sessões no período</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <p class="text-xs font-semibold text-gray-500 mb-1">Média Diária</p>
                <p class="text-3xl font-bold text-gray-900 mb-2">{{ $mediaDiaria }}</p>
                <p class="text-xs text-green-600 font-medium flex items-center gap-1">Média de sessões por dia</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <p class="text-xs font-semibold text-gray-500 mb-1">Total de Atendimentos</p>
                <p class="text-3xl font-bold text-gray-900 mb-2">{{ number_format($totalAtendimentos, 0, ',', '.') }}</p>
                <p class="text-xs text-blue-500 font-medium flex items-center gap-1">Quantidade de registros no sistema</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <p class="text-xs font-semibold text-gray-500 mb-1">Beneficiários Atendidos</p>
                <p class="text-3xl font-bold text-gray-900 mb-2">{{ number_format($beneficiariosAtendidos, 0, ',', '.') }}</p>
                <p class="text-xs text-orange-500 font-medium flex items-center gap-1">Total de Pacientes únicos</p>
            </div>
        </div>

        <div wire:key="charts-geral-{{ $mes }}-{{ $ano }}-{{ $unidade_id }}-{{ $terapia_id }}-{{ $convenio_id }}" class="space-y-6">
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h3 class="font-bold text-gray-800 mb-4">Sessões por Dia</h3>
                <div x-data="{
                    init() {
                        let rawData = @js($graficoDias);
                        let labels = rawData.map(d => {
                            let parts = d.data.split('-');
                            return parts[2] + '/' + parts[1]; // Formata para DD/MM
                        });
                        let series = rawData.map(d => d.total);

                        let options = {
                            chart: { type: 'bar', height: 300, toolbar: { show: false }, fontFamily: 'inherit' },
                            series: [{ name: 'Sessões', data: series }],
                            xaxis: { categories: labels, labels: { style: { colors: '#6b7280' } } },
                            yaxis: { labels: { style: { colors: '#6b7280' } } },
                            colors: ['#2dd4bf'],
                            plotOptions: {
                                bar: {
                                    borderRadius: 2,
                                    dataLabels: { position: 'top' }
                                }
                            },
                            dataLabels: {
                                enabled: true,
                                offsetY: -20,
                                style: { fontSize: '12px', colors: ['#374151'], fontWeight: 'bold' }
                            },
                            grid: { borderColor: '#f3f4f6', strokeDashArray: 4 }
                        };
                        new ApexCharts(this.$refs.chartDias, options).render();
                    }
                }">
                    <div x-ref="chartDias"></div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <h3 class="font-bold text-gray-800 mb-4">Ranking de Atendimentos por Terapia</h3>
                    <div x-data="{
                        init() {
                            let rawData = @js($graficoTerapias);
                            let options = {
                                chart: { type: 'bar', height: 350, toolbar: { show: false }, fontFamily: 'inherit' },
                                series: [{ name: 'Atendimentos', data: rawData.map(d => d.total) }],
                                xaxis: { categories: rawData.map(d => d.nome), labels: { style: { colors: '#6b7280' } } },
                                colors: ['#3b82f6', '#0ea5e9', '#06b6d4', '#14b8a6', '#22c55e', '#8b5cf6'],
                                plotOptions: {
                                    bar: {
                                        horizontal: true,
                                        distributed: true,
                                        borderRadius: 3,
                                        dataLabels: { position: 'top' }
                                    }
                                },
                                dataLabels: {
                                    enabled: true,
                                    textAnchor: 'start',
                                    offsetX: 5,
                                    dropShadow: { enabled: false },
                                    style: { fontSize: '12px', colors: ['#374151'], fontWeight: 'bold' }
                                },
                                legend: { show: false },
                                grid: { borderColor: '#f3f4f6', strokeDashArray: 4, xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } }
                            };
                            new ApexCharts(this.$refs.chartTerapias, options).render();
                        }
                    }">
                        <div x-ref="chartTerapias"></div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <h3 class="font-bold text-gray-800 mb-4">Ranking de Atendimentos por Unidade</h3>
                    <div x-data="{
                        init() {
                            let rawData = @js($graficoUnidades);
                            let options = {
                                chart: { type: 'bar', height: 350, toolbar: { show: false }, fontFamily: 'inherit' },
                                series: [{ name: 'Atendimentos', data: rawData.map(d => d.total) }],
                                xaxis: { categories: rawData.map(d => d.nome), labels: { style: { colors: '#6b7280' } } },
                                colors: ['#8b5cf6', '#ec4899', '#10b981', '#f59e0b'],
                                plotOptions: {
                                    bar: {
                                        horizontal: true,
                                        distributed: true,
                                        borderRadius: 3,
                                        dataLabels: { position: 'top' }
                                    }
                                },
                                dataLabels: {
                                    enabled: true,
                                    textAnchor: 'start',
                                    offsetX: 5,
                                    dropShadow: { enabled: false },
                                    style: { fontSize: '12px', colors: ['#374151'], fontWeight: 'bold' }
                                },
                                legend: { show: false },
                                grid: { borderColor: '#f3f4f6', strokeDashArray: 4, xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } }
                            };
                            new ApexCharts(this.$refs.chartUnidades, options).render();
                        }
                    }">
                        <div x-ref="chartUnidades"></div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <h3 class="font-bold text-gray-800 mb-4">Beneficiários Atendidos por Unidade</h3>
                    <div x-data="{
                        init() {
                            let rawData = @js($graficoBeneficiariosUnidade);
                            let options = {
                                chart: { type: 'bar', height: 250, toolbar: { show: false }, fontFamily: 'inherit' },
                                series: [{ name: 'Pacientes Únicos', data: rawData.map(d => d.total) }],
                                xaxis: { categories: rawData.map(d => d.nome), labels: { style: { colors: '#6b7280' } } },
                                colors: ['#f97316', '#ef4444', '#06b6d4', '#6366f1'],
                                plotOptions: {
                                    bar: {
                                        horizontal: true,
                                        distributed: true,
                                        borderRadius: 3,
                                        dataLabels: { position: 'top' }
                                    }
                                },
                                dataLabels: {
                                    enabled: true,
                                    textAnchor: 'start',
                                    offsetX: 5,
                                    dropShadow: { enabled: false },
                                    style: { fontSize: '12px', colors: ['#374151'], fontWeight: 'bold' }
                                },
                                legend: { show: false },
                                grid: { borderColor: '#f3f4f6', strokeDashArray: 4, xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } }
                            };
                            new ApexCharts(this.$refs.chartBeneficiarios, options).render();
                        }
                    }">
                        <div x-ref="chartBeneficiarios"></div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <h3 class="font-bold text-gray-800 mb-4">Ranking de Atendimentos por Convênio</h3>
                    <div x-data="{
                        init() {
                            let rawData = @js($graficoConvenios);
                            let options = {
                                chart: { type: 'bar', height: 250, toolbar: { show: false }, fontFamily: 'inherit' },
                                series: [{ name: 'Atendimentos', data: rawData.map(d => d.total) }],
                                xaxis: { categories: rawData.map(d => d.nome), labels: { style: { colors: '#6b7280' } } },
                                colors: ['#f59e0b', '#3b82f6', '#10b981', '#64748b'],
                                plotOptions: {
                                    bar: {
                                        horizontal: true,
                                        distributed: true,
                                        borderRadius: 3,
                                        dataLabels: { position: 'top' }
                                    }
                                },
                                dataLabels: {
                                    enabled: true,
                                    textAnchor: 'start',
                                    offsetX: 5,
                                    dropShadow: { enabled: false },
                                    style: { fontSize: '12px', colors: ['#374151'], fontWeight: 'bold' }
                                },
                                legend: { show: false },
                                grid: { borderColor: '#f3f4f6', strokeDashArray: 4, xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } }
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
                <p class="text-sm text-green-600 font-medium">{{ number_format($totalMelhorDiaAtual, 0, ',', '.') }} atendimentos no total</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <p class="text-xs font-semibold text-gray-500 mb-2 flex items-center gap-2"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg> Dia + Movimentado (Mês Anterior)</p>
                <p class="text-3xl font-bold text-gray-900 mb-2">{{ $melhorDiaAnterior }}</p>
                <p class="text-sm text-gray-500 font-medium">{{ number_format($totalMelhorDiaAnterior, 0, ',', '.') }} atendimentos no total</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <p class="text-xs font-semibold text-gray-500 mb-2 flex items-center gap-2"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg> Média Diária</p>
                <p class="text-3xl font-bold text-gray-900 mb-2">{{ $mediaDiaria }} /dia</p>
                <p class="text-sm text-blue-500 font-medium">Média por dia da semana</p>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 mb-8" wire:key="line-chart-{{ $mes }}-{{ $ano }}">
            <h3 class="font-bold text-gray-800 mb-6 text-lg">Comparativo: Mês Selecionado vs Mês Anterior</h3>
            <div x-data="{
                init() {
                    let options = {
                        chart: { type: 'line', height: 350, toolbar: { show: false }, fontFamily: 'inherit' },
                        series: [
                            { name: 'Mês Atual', data: @js($linhaAtual) },
                            { name: 'Mês Anterior', data: @js($linhaAnterior) }
                        ],
                        xaxis: { categories: @js($diasLabels), labels: { style: { colors: '#6b7280' } } },
                        colors: ['#3b82f6', '#9ca3af'],
                        stroke: { width: [4, 3], curve: 'smooth', dashArray: [0, 5] },
                        markers: { size: 6, hover: { size: 8 } },
                        dataLabels: { enabled: false },
                        legend: { position: 'bottom' },
                        grid: { borderColor: '#f3f4f6', strokeDashArray: 4 }
                    };
                    new ApexCharts(this.$refs.chartLine, options).render();
                }
            }">
                <div x-ref="chartLine"></div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100" wire:key="bar-chart-{{ $mes }}-{{ $ano }}">
            <h3 class="font-bold text-gray-800 mb-6 text-lg">Terapias por Dia da Semana (Mês Selecionado)</h3>
            <div x-data="{
                init() {
                    let options = {
                        chart: { type: 'bar', height: 400, stacked: false, toolbar: { show: false }, fontFamily: 'inherit' },
                        series: @js($graficoTerapiasSemana),
                        xaxis: { categories: ['Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'] },
                        colors: ['#3b82f6', '#2dd4bf', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'],
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
                            style: { fontSize: '10px', colors: ['#374151'], fontWeight: 'bold' },
                            formatter: function (val) {
                                return val > 0 ? val : ''; // Só mostra o número se for maior que zero para não poluir o gráfico
                            }
                        },
                        stroke: { show: true, width: 2, colors: ['transparent'] },
                        legend: { position: 'bottom' },
                        grid: { borderColor: '#f3f4f6', strokeDashArray: 4 }
                    };
                    new ApexCharts(this.$refs.chartWeek, options).render();
                }
            }">
                <div x-ref="chartWeek"></div>
            </div>
        </div>

    @endif

</div>