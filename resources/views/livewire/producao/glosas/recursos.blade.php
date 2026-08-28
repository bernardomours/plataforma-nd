@php
    $moeda = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
    $pct   = fn ($v) => number_format((float) $v, 2, ',', '.') . '%';

    $convRecursado = fn ($glosa, $recursado) => $glosa > 0 && $recursado !== null ? ((float) $recursado / (float) $glosa) * 100 : null;
    $convAcatado   = fn ($recursado, $acatado) => $recursado > 0 && $acatado !== null ? ((float) $acatado / (float) $recursado) * 100 : null;
@endphp

<div>
    {{-- Cabeçalho --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Acompanhamento de Recursos</h1>
        <p class="mt-1 text-sm text-gray-500">
            Controle manual do recurso de glosa junto ao convênio: quanto foi recursado e quanto foi acatado, por lote.
        </p>
    </div>

    {{-- Filtros --}}
    <div class="mb-6 rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 p-5 sm:flex-row sm:flex-wrap sm:items-end">
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

            <div class="sm:w-64">
                <label for="unidade" class="mb-1 block text-xs font-semibold text-gray-700">Unidade / Prestador</label>
                <select id="unidade" wire:model.live="unidade_id"
                        class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Todas as unidades</option>
                    @foreach($unidadesLista as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="sm:w-56">
                <label for="status" class="mb-1 block text-xs font-semibold text-gray-700">Situação do recurso</label>
                <select id="status" wire:model.live="status"
                        class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Todas</option>
                    <option value="sem_registro">Sem recurso registrado</option>
                    @foreach($statusOptions as $valor => $rotulo)
                        <option value="{{ $valor }}">{{ $rotulo }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex-1 sm:min-w-[14rem]">
                <label for="busca" class="mb-1 block text-xs font-semibold text-gray-700">Buscar prestador</label>
                <input type="text" id="busca" wire:model.live.debounce.400ms="busca" placeholder="Nome ou código do prestador"
                       class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <button type="button" wire:click="limparFiltros"
                        class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                    Limpar filtros
                </button>
            </div>
        </div>
    </div>

    <div wire:loading.class="opacity-50" wire:target="competencia,unidade_id,status,busca" class="transition-opacity">

        {{-- KPIs --}}
        <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3 xl:grid-cols-5">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-500">Lotes com Glosa</h3>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($kpis['total_lotes'], 0, ',', '.') }}</p>
                <p class="mt-2 text-xs text-gray-400">{{ number_format($kpis['sem_recurso'], 0, ',', '.') }} sem recurso registrado</p>
            </div>

            <div class="rounded-xl border border-rose-200 bg-rose-50 p-5 shadow-sm">
                <h3 class="mb-2 text-xs font-bold uppercase tracking-wider text-rose-700">Valor Glosado</h3>
                <p class="text-2xl font-bold text-rose-700">{{ $moeda($kpis['total_glosado']) }}</p>
            </div>

            <div class="rounded-xl border border-blue-200 bg-blue-50 p-5 shadow-sm">
                <h3 class="mb-2 text-xs font-bold uppercase tracking-wider text-blue-700">Valor Recursado</h3>
                <p class="text-2xl font-bold text-blue-700">{{ $moeda($kpis['total_recursado']) }}</p>
                <p class="mt-2 text-xs text-blue-700">{{ $pct($kpis['conversao_recursado']) }} da glosa</p>
            </div>

            <div class="rounded-xl border border-green-400 bg-green-50 p-5 shadow-sm">
                <h3 class="mb-2 text-xs font-bold uppercase tracking-wider text-green-600">Valor Acatado</h3>
                <p class="text-2xl font-bold text-emerald-600">{{ $moeda($kpis['total_acatado']) }}</p>
                <p class="mt-2 text-xs text-green-600">{{ $pct($kpis['conversao_acatado']) }} do recursado</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-500">Conversão Geral</h3>
                <p class="text-2xl font-bold text-gray-900">{{ $pct($kpis['conversao_acatado']) }}</p>
                <p class="mt-2 text-xs text-gray-400">acatado sobre recursado</p>
            </div>
        </div>

        {{-- Tabela --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1200px] text-left text-sm">
                    <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-600">
                        <tr>
                            <th class="px-4 py-3 whitespace-nowrap">Prestador</th>
                            <th class="px-4 py-3 whitespace-nowrap">Competência</th>
                            <th class="px-4 py-3 text-right whitespace-nowrap">Apresentado</th>
                            <th class="px-4 py-3 text-right whitespace-nowrap">Liberado</th>
                            <th class="px-4 py-3 text-right whitespace-nowrap">Glosa</th>
                            <th class="px-4 py-3 text-right whitespace-nowrap">% Glosado</th>
                            <th class="border-l border-blue-100 bg-blue-50/60 px-4 py-3 whitespace-nowrap">Lote</th>
                            <th class="bg-blue-50/60 px-4 py-3 text-right whitespace-nowrap">Recursado</th>
                            <th class="bg-blue-50/60 px-4 py-3 text-right whitespace-nowrap">% Conv.</th>
                            <th class="bg-blue-50/60 px-4 py-3 text-right whitespace-nowrap">Acatado</th>
                            <th class="bg-blue-50/60 px-4 py-3 text-right whitespace-nowrap">% Conv.</th>
                            <th class="bg-blue-50/60 px-4 py-3 whitespace-nowrap">Status</th>
                            <th class="bg-blue-50/60 px-4 py-3 text-right whitespace-nowrap">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($lotes as $batch)
                            @php
                                $recurso = $batch->recurso;
                                $percGlosado = $batch->vl_apresentado > 0 ? ($batch->vl_glosa / $batch->vl_apresentado) * 100 : 0;
                                $pctRec = $convRecursado($batch->vl_glosa, $recurso?->valor_recursado);
                                $pctAcat = $convAcatado($recurso?->valor_recursado, $recurso?->valor_acatado);
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-gray-900">{{ $batch->prestador_nome }}</p>
                                    <p class="text-xs text-gray-400">{{ $batch->prestador_codigo }}</p>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap capitalize">{{ $batch->competencia->translatedFormat('M/Y') }}</td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">{{ $moeda($batch->vl_apresentado) }}</td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">{{ $moeda($batch->vl_liberado) }}</td>
                                <td class="px-4 py-3 text-right whitespace-nowrap font-semibold text-rose-700">{{ $moeda($batch->vl_glosa) }}</td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">{{ $pct($percGlosado) }}</td>

                                <td class="border-l border-blue-100 bg-blue-50/40 px-4 py-3 whitespace-nowrap">{{ $recurso?->lote ?? '—' }}</td>
                                <td class="bg-blue-50/40 px-4 py-3 text-right whitespace-nowrap">
                                    {{ $recurso?->valor_recursado !== null ? $moeda($recurso->valor_recursado) : '—' }}
                                </td>
                                <td class="bg-blue-50/40 px-4 py-3 text-right whitespace-nowrap">{{ $pctRec !== null ? $pct($pctRec) : '—' }}</td>
                                <td class="bg-blue-50/40 px-4 py-3 text-right whitespace-nowrap">
                                    {{ $recurso?->valor_acatado !== null ? $moeda($recurso->valor_acatado) : '—' }}
                                </td>
                                <td class="bg-blue-50/40 px-4 py-3 text-right whitespace-nowrap">{{ $pctAcat !== null ? $pct($pctAcat) : '—' }}</td>
                                <td class="bg-blue-50/40 px-4 py-3 whitespace-nowrap">
                                    @if($recurso?->status)
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap
                                            {{ $recurso->status === 'pagamento_efetuado' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">
                                            {{ $recurso->statusLabel() }}
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="bg-blue-50/40 px-4 py-3 text-right whitespace-nowrap">
                                    <button type="button" wire:click="abrirModal({{ $batch->id }})"
                                            class="text-sm font-semibold text-blue-600 hover:text-blue-800">
                                        {{ $recurso ? 'Editar' : 'Registrar' }}
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="px-4 py-10 text-center text-sm text-gray-500">
                                    Nenhum lote com glosa encontrado para os filtros selecionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($lotes->hasPages())
                <div class="border-t border-gray-100 px-4 py-3">
                    {{ $lotes->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Modal de registro/edição --}}
    @if($isModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-center justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" wire:click="fecharModal"></div>
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

                <div class="inline-block transform overflow-hidden rounded-xl border border-gray-200 bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                    <form wire:submit="salvar">
                        <div class="bg-white px-6 pt-5 pb-6">
                            <h3 class="mb-4 border-b pb-2 text-lg font-bold leading-6 text-gray-900" id="modal-title">
                                Acompanhamento de Recurso
                            </h3>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div class="md:col-span-2">
                                    <label class="mb-1 block text-sm font-semibold text-gray-700">Lote</label>
                                    <input type="text" wire:model="lote" maxlength="30" placeholder="Número do lote do recurso"
                                           class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @error('lote') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="mb-1 block text-sm font-semibold text-gray-700">Valor Recursado (R$)</label>
                                    <input type="number" step="0.01" min="0" wire:model="valor_recursado" placeholder="0,00"
                                           class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @error('valor_recursado') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="mb-1 block text-sm font-semibold text-gray-700">Valor Acatado (R$)</label>
                                    <input type="number" step="0.01" min="0" wire:model="valor_acatado" placeholder="0,00"
                                           class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @error('valor_acatado') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>

                                <div class="md:col-span-2">
                                    <label class="mb-1 block text-sm font-semibold text-gray-700">Status</label>
                                    <select wire:model="modal_status"
                                            class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Sem status definido</option>
                                        @foreach($statusOptions as $valor => $rotulo)
                                            <option value="{{ $valor }}">{{ $rotulo }}</option>
                                        @endforeach
                                    </select>
                                    @error('modal_status') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                        <div class="rounded-b-xl border-t border-gray-100 bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                            <button type="submit" class="inline-flex w-full justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-blue-700 sm:ml-3 sm:w-auto sm:text-sm">
                                Salvar
                            </button>
                            <button type="button" wire:click="fecharModal" class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
