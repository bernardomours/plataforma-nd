@php
    $moeda = fn ($v) => 'R$ ' . number_format($v, 2, ',', '.');
    $num   = fn ($v) => number_format($v, 0, ',', '.');
    $pct   = fn ($v) => number_format($v, 2, ',', '.') . '%';

    $picoGlosa = max(1, $evolucao->max('glosa'));
    $temFiltroDeLista = $codigo !== '' || $busca !== '' || $situacao !== 'glosados';
    $rotuloEscopo = $competencia
        ? \Carbon\Carbon::parse($competencia)->translatedFormat('F/Y')
        : 'todas as competências';
@endphp

<div>
    {{-- Cabeçalho --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Relatórios de Glosa</h1>
        <p class="mt-1 text-sm text-gray-500">
            Demonstrativo da produção, antes no BI, agora diretamente na plataforma.
        </p>
    </div>

    {{-- Explicação: o que a tela mostra e o que ela não faz --}}
    <details class="mb-6 rounded-xl border border-blue-200 bg-blue-50 p-4">
        <summary class="flex cursor-pointer items-center gap-2 text-sm font-bold text-blue-900">
            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Como ler esta tela
        </summary>
        <div class="mt-3 space-y-2 border-t border-blue-200 pt-3 text-sm leading-relaxed text-blue-900">
            <p>
                <strong>Apresentado</strong> é o que a clínica cobrou do convênio.
                <strong>Glosa</strong> é a parte recusada. <strong>Liberado</strong> é o que foi pago.
                O relatório chega cerca de dois meses depois do atendimento.
            </p>
            <p>
                A glosa aqui é <strong>informativa</strong>: ela não altera o repasse já apurado
                no Fechamento, porque quando o relatório chega a competência já foi paga.
                Serve para saber o que corrigir daqui em diante.
            </p>
            <p>
                O vínculo com o atendimento é feito pelo <strong>número da guia</strong>. Itens de
                antes de fevereiro/2026 não têm atendimento na plataforma — entram nos valores e no
                ranking por motivo, mas não têm profissional identificável.
            </p>
        </div>
    </details>

    {{-- Filtros da página --}}
    <div class="mb-6 rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-end">
            <div class="sm:w-56">
                <label for="competencia" class="mb-1 block text-xs font-semibold text-gray-700">Competência</label>
                <select id="competencia" wire:model.live="competencia"
                        class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Todas as competências</option>
                    @foreach($competenciasLista as $c)
                        <option value="{{ $c->toDateString() }}">{{ ucfirst($c->translatedFormat('F/Y')) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="sm:w-80">
                <label for="unidade" class="mb-1 block text-xs font-semibold text-gray-700">Unidade / Prestador</label>
                <select id="unidade" wire:model.live="unidade_id"
                        class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Todas as unidades</option>
                    @foreach($unidadesLista as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>

            <p class="flex-1 text-xs text-gray-500 sm:pb-2 sm:text-right">
                Os números abaixo referem-se a <span class="font-semibold text-gray-700">{{ $rotuloEscopo }}</span>.
            </p>
        </div>
    </div>

    <div wire:loading.class="opacity-50" wire:target="competencia,unidade_id" class="transition-opacity">

        {{-- KPIs --}}
        <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-500">Valor Apresentado</h3>
                <p class="text-2xl font-bold text-gray-900">{{ $moeda($kpis['apresentado']) }}</p>
                <p class="mt-2 text-xs text-gray-400">{{ $num($kpis['itens']) }} itens cobrados</p>
            </div>

            <div class="rounded-xl border border-rose-200 bg-rose-50 p-5 shadow-sm">
                <h3 class="mb-2 text-xs font-bold uppercase tracking-wider text-rose-700">Valor Glosado</h3>
                <p class="text-2xl font-bold text-rose-700">{{ $moeda($kpis['glosa']) }}</p>
                <p class="mt-2 text-xs text-rose-700">{{ $num($kpis['glosados']) }} itens recusados</p>
            </div>

            <div class="rounded-xl border border-green-400 bg-green-50 p-5 shadow-sm">
                <h3 class="mb-2 text-xs font-bold uppercase tracking-wider text-green-500">Valor Liberado</h3>
                <p class="text-2xl font-bold text-emerald-600">{{ $moeda($kpis['liberado']) }}</p>
            </div>

            <div class="rounded-xl border p-5 shadow-sm {{ $kpis['percentual'] >= 5 ? 'border-amber-300 bg-amber-50' : 'border-gray-200 bg-white' }}">
                <h3 class="mb-2 text-xs font-bold uppercase tracking-wider {{ $kpis['percentual'] >= 5 ? 'text-amber-700' : 'text-gray-500' }}">
                    Percentual de Glosa
                </h3>
                <p class="text-2xl font-bold {{ $kpis['percentual'] >= 5 ? 'text-amber-700' : 'text-gray-900' }}">
                    {{ $pct($kpis['percentual']) }}
                </p>
                <p class="mt-2 text-xs {{ $kpis['percentual'] >= 5 ? 'text-amber-700' : 'text-gray-400' }}">
                    Glosa sobre o apresentado
                </p>
            </div>
        </div>

        {{-- Evolução --}}
        <div class="mb-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-1 flex flex-wrap items-center justify-between gap-2">
                <h3 class="font-bold text-gray-800">Evolução por Competência</h3>
                <div class="flex items-center gap-4 text-xs text-gray-500">
                    <span class="flex items-center gap-1.5">
                        <span class="inline-block h-2.5 w-2.5 rounded-sm bg-rose-500"></span> Valor glosado
                    </span>
                    <span>Rótulo acima da barra: % de glosa do mês</span>
                </div>
            </div>
            <p class="mb-5 text-xs text-gray-500">
                Clique numa barra para filtrar a página por aquela competência.
            </p>

            @if($evolucao->isEmpty())
                <p class="py-8 text-center text-sm text-gray-500">Nenhuma competência importada ainda.</p>
            @else
                <div class="flex h-52 items-end gap-2 overflow-x-auto pb-1">
                    @foreach($evolucao as $ponto)
                        <button type="button"
                                wire:click="$set('competencia', '{{ $ponto->atual ? '' : $ponto->competencia->toDateString() }}')"
                                title="{{ ucfirst($ponto->titulo) }} — apresentado {{ $moeda($ponto->apresentado) }}, glosa {{ $moeda($ponto->glosa) }}"
                                class="group flex min-w-[52px] flex-1 flex-col items-center justify-end gap-1.5 rounded-lg px-1 pt-1 transition-colors hover:bg-gray-50 {{ $ponto->atual ? 'bg-blue-50' : '' }}">
                            <span class="text-[11px] font-bold {{ $ponto->percentual >= 5 ? 'text-rose-600' : 'text-gray-500' }}">
                                {{ number_format($ponto->percentual, 1, ',', '.') }}%
                            </span>
                            <div class="w-full rounded-t transition-colors {{ $ponto->atual ? 'bg-rose-600' : 'bg-rose-400 group-hover:bg-rose-500' }}"
                                 style="height: {{ max(3, (int) round($ponto->glosa / $picoGlosa * 120)) }}px"></div>
                            <span class="text-[11px] font-medium {{ $ponto->atual ? 'text-blue-700' : 'text-gray-400' }}">
                                {{ $ponto->rotulo }}
                            </span>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Rankings --}}
        <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-3">

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="font-bold text-gray-800">Ranking por Motivo</h3>
                <p class="mb-4 mt-0.5 text-xs text-gray-500">
                    Quantas vezes cada motivo apareceu. Um item pode ter mais de um.
                </p>

                @forelse($motivos as $m)
                    @php $largura = $motivos->max('ocorrencias') ?: 1; @endphp
                    <button type="button" wire:click="$set('codigo', '{{ $codigo === $m->codigo ? '' : $m->codigo }}')"
                            class="mb-2.5 block w-full rounded-lg p-2 text-left transition-colors hover:bg-gray-50 {{ $codigo === $m->codigo ? 'bg-blue-50 ring-1 ring-blue-200' : '' }}">
                        <div class="flex items-baseline justify-between gap-2">
                            <span class="font-mono text-xs font-bold text-gray-700">{{ $m->codigo }}</span>
                            <span class="text-xs font-bold tabular-nums text-gray-900">{{ $num($m->ocorrencias) }}</span>
                        </div>
                        <div class="my-1 h-1.5 w-full rounded-full bg-gray-100">
                            <div class="h-1.5 rounded-full bg-blue-500" style="width: {{ round($m->ocorrencias / $largura * 100, 1) }}%"></div>
                        </div>
                        <p class="truncate text-[11px] text-gray-500" title="{{ $m->descricao }}">{{ $m->descricao }}</p>
                    </button>
                @empty
                    <p class="py-6 text-center text-sm text-gray-500">Nenhuma glosa nesta seleção.</p>
                @endforelse
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="font-bold text-gray-800">Ranking por Beneficiário</h3>
                <p class="mb-4 mt-0.5 text-xs text-gray-500">
                    Pacientes com mais itens glosados no período.
                </p>

                @forelse($beneficiarios as $b)
                    <div class="flex items-baseline justify-between gap-3 border-b border-gray-100 py-2 last:border-0">
                        <span class="truncate text-sm text-gray-700" title="{{ $b->nome }}">{{ $b->nome }}</span>
                        <span class="shrink-0 text-right">
                            <span class="text-xs font-bold tabular-nums text-gray-900">{{ $num($b->ocorrencias) }}</span>
                            <span class="ml-1 text-[11px] text-gray-400">{{ $moeda($b->valor) }}</span>
                        </span>
                    </div>
                @empty
                    <p class="py-6 text-center text-sm text-gray-500">Nenhuma glosa nesta seleção.</p>
                @endforelse
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="font-bold text-gray-800">Ranking por Profissional</h3>
                <p class="mb-4 mt-0.5 text-xs text-gray-500">
                    Guias distintas com glosa, pelo nome que consta no relatório do convênio.
                </p>

                @forelse($profissionais as $p)
                    <div class="flex items-baseline justify-between gap-3 border-b border-gray-100 py-2 last:border-0">
                        <span class="truncate text-sm text-gray-700" title="{{ $p->nome }}">
                            {{ $p->nome }}
                            @if($p->inativo)
                                <span class="ml-1 rounded bg-gray-100 px-1 py-0.5 text-[10px] font-bold text-gray-500">INATIVO</span>
                            @elseif(! $p->vinculado)
                                <span class="ml-1 rounded bg-amber-50 px-1 py-0.5 text-[10px] font-bold text-amber-700"
                                      title="Nenhuma guia deste profissional achou atendimento na plataforma">SEM VÍNCULO</span>
                            @endif
                        </span>
                        <span class="shrink-0 text-right">
                            <span class="text-xs font-bold tabular-nums text-gray-900">{{ $moeda($p->valor) }}</span>
                            <span class="ml-1 text-[11px] text-gray-400">{{ $num($p->ocorrencias) }}</span>
                        </span>
                    </div>
                @empty
                    <p class="py-6 text-center text-sm text-gray-500">Nenhuma glosa nesta seleção.</p>
                @endforelse

                @if($semVinculo > 0)
                    <p class="mt-4 rounded-lg bg-gray-50 p-3 text-[11px] leading-relaxed text-gray-600">
                        {{ $num($semVinculo) }} dos itens glosados não acharam atendimento na plataforma
                        pelo número da guia. Eles <strong>entram</strong> neste ranking pelo nome do
                        relatório, mas não dá para abrir o atendimento correspondente.
                    </p>
                @endif
            </div>
        </div>

        {{-- Lista --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 p-5">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h3 class="font-bold text-gray-800">Itens do Relatório</h3>
                        <p class="mt-0.5 text-xs text-gray-500">
                            {{ $num($itens->total()) }}
                            {{ $itens->total() === 1 ? 'item encontrado' : 'itens encontrados' }}
                            em {{ $rotuloEscopo }}.
                        </p>
                    </div>
                    @if($temFiltroDeLista)
                        <button type="button" wire:click="limparFiltrosDaLista"
                                class="text-sm font-medium text-red-600 transition-colors hover:text-red-800">
                            Limpar filtros da lista
                        </button>
                    @endif
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label for="situacao" class="mb-1 block text-xs font-semibold text-gray-700">Situação</label>
                        <select id="situacao" wire:model.live="situacao"
                                class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="glosados">Apenas glosados</option>
                            <option value="nao_conciliados">Glosados sem atendimento vinculado</option>
                            <option value="todos">Todos os itens</option>
                        </select>
                    </div>

                    <div>
                        <label for="codigo" class="mb-1 block text-xs font-semibold text-gray-700">Motivo</label>
                        <select id="codigo" wire:model.live="codigo"
                                class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Todos os motivos</option>
                            @foreach($codigosLista as $c)
                                <option value="{{ $c->codigo }}">{{ $c->codigo }} — {{ \Illuminate\Support\Str::limit($c->descricao, 45) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="busca" class="mb-1 block text-xs font-semibold text-gray-700">Pesquisar</label>
                        <div class="relative">
                            <input id="busca" type="search" wire:model.live.debounce.300ms="busca"
                                   placeholder="Beneficiário, guia ou profissional..."
                                   class="w-full rounded-md border-gray-300 pr-9 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <span wire:loading wire:target="busca" class="absolute inset-y-0 right-0 flex items-center pr-3">
                                <svg class="h-4 w-4 animate-spin text-blue-500" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                                    <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50 text-xs font-bold uppercase tracking-wider text-gray-800">
                            <th class="px-4 py-3">Data</th>
                            <th class="px-4 py-3">Beneficiário</th>
                            <th class="px-4 py-3">Guia</th>
                            <th class="px-4 py-3">Procedimento</th>
                            <th class="px-4 py-3 text-right">Apresentado</th>
                            <th class="px-4 py-3 text-right">Glosa</th>
                            <th class="px-4 py-3">Motivo</th>
                            <th class="px-4 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($itens as $item)
                            <tr class="transition-colors hover:bg-gray-50">
                                <td class="whitespace-nowrap px-4 py-3 text-gray-600">
                                    {{ $item->dt_item?->format('d/m/Y') ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="font-medium text-gray-900">{{ $item->beneficiario_nome ?? '—' }}</span>
                                    @if($item->professional)
                                        <span class="block text-xs text-gray-400">{{ $item->professional->name }}</span>
                                    @elseif($item->medico_nome)
                                        <span class="block text-xs text-gray-400" title="Não vinculado a um profissional da plataforma">
                                            {{ $item->medico_nome }} <span class="text-gray-300">· não vinculado</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-gray-600">{{ $item->guia ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="block max-w-xs truncate text-gray-700" title="{{ $item->item_descricao }}">
                                        {{ $item->item_descricao ?? '—' }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums text-gray-600">
                                    {{ $moeda($item->vl_apresentado) }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-bold tabular-nums {{ $item->vl_glosa > 0 ? 'text-rose-600' : 'text-gray-300' }}">
                                    {{ $item->vl_glosa > 0 ? $moeda($item->vl_glosa) : '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    @forelse($item->reasons as $r)
                                        <span class="mb-0.5 mr-1 inline-flex items-center rounded-md border border-rose-200 bg-rose-50 px-2 py-0.5 font-mono text-[11px] font-bold text-rose-700"
                                              title="{{ $r->code->descricao ?? $r->descricao }}">
                                            {{ $r->codigo }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-gray-300">—</span>
                                    @endforelse
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <button type="button" wire:click="verDetalhe({{ $item->id }})"
                                            class="text-xs font-medium text-blue-600 hover:text-blue-900">
                                        Detalhes
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-10 text-center text-sm text-gray-500">
                                    Nenhum item encontrado com os filtros aplicados.
                                    @if($temFiltroDeLista)
                                        <button type="button" wire:click="limparFiltrosDaLista"
                                                class="ml-1 font-semibold text-blue-600 hover:text-blue-700">
                                            Limpar filtros da lista
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($itens->hasPages())
                <div class="border-t border-gray-100 px-5 py-4">
                    {{ $itens->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Detalhe --}}
    @if($detalhe)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4"
             wire:click.self="fecharDetalhe" wire:keydown.escape.window="fecharDetalhe">
            <div class="max-h-[85vh] w-full max-w-2xl overflow-y-auto rounded-xl bg-white shadow-xl">

                <div class="flex items-start justify-between gap-4 border-b border-gray-100 p-5">
                    <div>
                        <h3 class="font-bold text-gray-900">Detalhe do Item</h3>
                        <p class="mt-0.5 text-xs text-gray-500">
                            Guia {{ $detalhe->guia ?? '—' }} ·
                            {{ ucfirst($detalhe->competencia->translatedFormat('F/Y')) }} ·
                            {{ $detalhe->batch->unit->name ?? '—' }}
                        </p>
                    </div>
                    <button type="button" wire:click="fecharDetalhe" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="space-y-5 p-5">
                    <div class="grid grid-cols-3 gap-3">
                        <div class="rounded-lg border border-gray-200 p-3">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-500">Apresentado</p>
                            <p class="mt-1 font-bold text-gray-900">{{ $moeda($detalhe->vl_apresentado) }}</p>
                        </div>
                        <div class="rounded-lg border border-rose-200 bg-rose-50 p-3">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-rose-700">Glosa</p>
                            <p class="mt-1 font-bold text-rose-700">{{ $moeda($detalhe->vl_glosa) }}</p>
                        </div>
                        <div class="rounded-lg border border-gray-200 p-3">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-500">Liberado</p>
                            <p class="mt-1 font-bold text-emerald-600">{{ $moeda($detalhe->vl_liberado) }}</p>
                        </div>
                    </div>

                    <dl class="divide-y divide-gray-100 text-sm">
                        <div class="flex justify-between gap-4 py-2">
                            <dt class="text-gray-500">Data do item</dt>
                            <dd class="text-right font-medium text-gray-900">{{ $detalhe->dt_item?->format('d/m/Y') ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 py-2">
                            <dt class="text-gray-500">Beneficiário</dt>
                            <dd class="text-right font-medium text-gray-900">{{ $detalhe->beneficiario_nome ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 py-2">
                            <dt class="text-gray-500">Procedimento</dt>
                            <dd class="text-right font-medium text-gray-900">
                                {{ $detalhe->item_descricao ?? '—' }}
                                <span class="block font-mono text-xs font-normal text-gray-400">{{ $detalhe->item_codigo }}</span>
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4 py-2">
                            <dt class="text-gray-500">Quantidade</dt>
                            <dd class="text-right font-medium text-gray-900">{{ rtrim(rtrim(number_format($detalhe->qt_item, 4, ',', '.'), '0'), ',') }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 py-2">
                            <dt class="text-gray-500">Conta / Lote</dt>
                            <dd class="text-right font-mono text-xs text-gray-900">{{ $detalhe->conta ?? '—' }} / {{ $detalhe->lote ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 py-2">
                            <dt class="text-gray-500">Profissional no relatório</dt>
                            <dd class="text-right font-medium text-gray-900">{{ $detalhe->medico_nome ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 py-2">
                            <dt class="text-gray-500">Atendimento na plataforma</dt>
                            <dd class="text-right font-medium">
                                @if($detalhe->appointment)
                                    <span class="text-gray-900">
                                        {{ $detalhe->professional->name ?? 'Profissional não identificado' }}
                                    </span>
                                    <span class="block text-xs font-normal text-gray-400">
                                        vinculado pela guia {{ $detalhe->guia }}
                                    </span>
                                @else
                                    <span class="text-amber-700">Não encontrado</span>
                                    <span class="block text-xs font-normal text-gray-400">
                                        A guia não existe em nenhum atendimento
                                    </span>
                                @endif
                            </dd>
                        </div>
                    </dl>

                    <div>
                        <h4 class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-500">
                            {{ $detalhe->reasons->count() === 1 ? 'Motivo' : 'Motivos' }}
                        </h4>

                        @forelse($detalhe->reasons as $r)
                            <div class="mb-2 rounded-lg border border-gray-200 p-3 last:mb-0">
                                <div class="flex items-center gap-2">
                                    <span class="rounded-md border border-rose-200 bg-rose-50 px-2 py-0.5 font-mono text-[11px] font-bold text-rose-700">
                                        {{ $r->codigo }}
                                    </span>
                                    <span class="text-[11px] uppercase tracking-wider text-gray-400">{{ $r->tipo }}</span>
                                </div>
                                <p class="mt-1.5 text-sm text-gray-700">{{ $r->code->descricao ?? $r->descricao }}</p>
                                @if($r->code?->orientacao)
                                    <p class="mt-2 rounded bg-blue-50 p-2 text-xs text-blue-900">{{ $r->code->orientacao }}</p>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">Este item não foi glosado.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
