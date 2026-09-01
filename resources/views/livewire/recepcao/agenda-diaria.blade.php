<div class="nd-ui">

    @if (session()->has('message'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" x-transition
             class="mb-4 flex items-center gap-2 rounded-lg border px-4 py-3 text-sm font-medium"
             style="background: var(--success-soft); border-color: #a7f3d0; color: var(--success-strong)">
            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition
             class="mb-4 flex items-center gap-2 rounded-lg border px-4 py-3 text-sm font-medium"
             style="background: var(--danger-soft); border-color: #fecaca; color: var(--danger-strong)">
            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('error') }}
        </div>
    @endif

    {{-- Cabeçalho: navegação de dia + unidade + placar do dia --}}
    <div class="nd-card mb-5 p-4 sm:p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-3">
                <button type="button" wire:click="irParaOntem" class="nd-btn-ghost flex h-9 w-9 items-center justify-center rounded-md transition-colors" title="Dia anterior">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                </button>

                <div class="min-w-[180px] text-center sm:min-w-[220px]">
                    <p class="nd-eyebrow">{{ $dataCarbon->isToday() ? 'Hoje' : $dataCarbon->translatedFormat('l') }}</p>
                    <p class="text-lg nd-title capitalize">{{ $dataCarbon->translatedFormat('d \d\e F \d\e Y') }}</p>
                </div>

                <button type="button" wire:click="irParaAmanha" class="nd-btn-ghost flex h-9 w-9 items-center justify-center rounded-md transition-colors" title="Próximo dia">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>

                @unless($dataCarbon->isToday())
                    <button type="button" wire:click="irParaHoje" class="text-sm font-semibold transition-colors hover:opacity-75" style="color: var(--accent)">
                        Voltar para hoje
                    </button>
                @endunless
            </div>

            <div class="flex flex-wrap items-center gap-3">
                @if($unidadesFiltro->count() > 1)
                    <select wire:model.live="unit_id" class="nd-select px-3 text-sm" style="width: 180px">
                        <option value="">Todas as clínicas</option>
                        @foreach($unidadesFiltro as $unidade)
                            <option value="{{ $unidade->id }}">{{ $unidade->city ?? $unidade->name }}</option>
                        @endforeach
                    </select>
                @endif

                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold" style="background: var(--surface-2); color: var(--ink-2); border: 1px solid var(--line)">
                        <span class="h-1.5 w-1.5 rounded-full" style="background: var(--ink-3)"></span>
                        {{ $totalPendente }} pendente{{ $totalPendente === 1 ? '' : 's' }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold" style="background: var(--success-soft); color: var(--success-strong)">
                        <span class="h-1.5 w-1.5 rounded-full" style="background: var(--success)"></span>
                        {{ $totalRealizado }} atendido{{ $totalRealizado === 1 ? '' : 's' }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold" style="background: var(--danger-soft); color: var(--danger-strong)">
                        <span class="h-1.5 w-1.5 rounded-full" style="background: var(--danger)"></span>
                        {{ $totalFalta }} falta{{ $totalFalta === 1 ? '' : 's' }}
                    </span>
                    @if($totalUnimed > 0)
                        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold" style="background: var(--surface-2); color: var(--ink-3); border: 1px solid var(--line)" title="Só visualização — o convênio já tem relatório próprio">
                            <span class="h-1.5 w-1.5 rounded-full" style="background: var(--ink-3)"></span>
                            {{ $totalUnimed }} Unimed (visualização)
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Filtros: opções vêm só de quem está na grade do dia/unidade selecionada.
             Largura fixa (não min-width) de propósito: .nd-select tem width:100% por
             padrão (pensado pra formulário empilhado), então dentro deste flex row cada
             select ocupava a linha inteira sozinho e empurrava os outros pra baixo —
             min-width sozinho não vence um width:100% já definido. --}}
        <div class="mt-4 flex flex-wrap items-center gap-3 border-t pt-4" style="border-color: var(--line)">
            {{-- Paciente (combobox com pesquisa) — mesmo padrão de Terapias Realizadas --}}
            <div x-data="{
                    open: false,
                    search: '',
                    options: [
                        { value: '', label: 'Todos os pacientes' },
                        @foreach($pacientesFiltro as $paciente)
                            { value: '{{ $paciente->id }}', label: '{!! addslashes($paciente->name) !!}' },
                        @endforeach
                    ],
                    get filteredOptions() {
                        if (this.search === '') return this.options;
                        return this.options.filter(i => i.label.toLowerCase().includes(this.search.toLowerCase()));
                    },
                    get selectedLabel() {
                        let selectedOpt = this.options.find(i => i.value == $wire.filtro_patient_id);
                        return selectedOpt ? selectedOpt.label : 'Todos os pacientes';
                    }
                }"
                class="relative" style="width: 220px"
            >
                <button type="button" @click="open = !open"
                        class="flex min-h-[38px] w-full items-center justify-between gap-2 rounded-md border bg-white px-3 py-2 text-left text-sm transition-colors"
                        style="border-color: var(--line)">
                    <span x-text="selectedLabel" class="truncate" style="color: var(--ink)"></span>
                    <svg class="h-4 w-4 shrink-0 transition-transform" :class="{ 'rotate-180': open }" style="color: var(--ink-3)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open" @click.away="open = false" x-cloak
                     class="absolute z-30 mt-1 w-full overflow-hidden rounded-md border bg-white shadow-lg"
                     style="border-color: var(--line)">
                    <div class="border-b p-2" style="border-color: var(--line); background: var(--surface-2)">
                        <input type="text" x-model="search" placeholder="Pesquisar paciente..."
                               class="w-full rounded border-0 bg-white px-2 py-1.5 text-sm shadow-sm"
                               style="border: 1px solid var(--line)">
                    </div>
                    <ul class="max-h-52 overflow-y-auto text-sm">
                        <template x-for="option in filteredOptions" :key="option.value">
                            <li @click="$wire.set('filtro_patient_id', option.value); open = false; search = ''"
                                class="cursor-pointer px-3 py-2 transition-colors hover:bg-gray-50"
                                :class="{ 'font-semibold': $wire.filtro_patient_id == option.value }"
                                :style="$wire.filtro_patient_id == option.value ? 'background: var(--accent-soft); color: var(--accent-strong)' : 'color: var(--ink)'">
                                <span x-text="option.label"></span>
                            </li>
                        </template>
                        <li x-show="filteredOptions.length === 0" class="px-3 py-2 text-center" style="color: var(--ink-3)">Nenhum encontrado</li>
                    </ul>
                </div>
            </div>

            {{-- Profissional (combobox com pesquisa) --}}
            <div x-data="{
                    open: false,
                    search: '',
                    options: [
                        { value: '', label: 'Todos os profissionais' },
                        @foreach($profissionaisFiltro as $prof)
                            { value: '{{ $prof->id }}', label: '{!! addslashes($prof->name) !!}' },
                        @endforeach
                    ],
                    get filteredOptions() {
                        if (this.search === '') return this.options;
                        return this.options.filter(i => i.label.toLowerCase().includes(this.search.toLowerCase()));
                    },
                    get selectedLabel() {
                        let selectedOpt = this.options.find(i => i.value == $wire.filtro_professional_id);
                        return selectedOpt ? selectedOpt.label : 'Todos os profissionais';
                    }
                }"
                class="relative" style="width: 220px"
            >
                <button type="button" @click="open = !open"
                        class="flex min-h-[38px] w-full items-center justify-between gap-2 rounded-md border bg-white px-3 py-2 text-left text-sm transition-colors"
                        style="border-color: var(--line)">
                    <span x-text="selectedLabel" class="truncate" style="color: var(--ink)"></span>
                    <svg class="h-4 w-4 shrink-0 transition-transform" :class="{ 'rotate-180': open }" style="color: var(--ink-3)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open" @click.away="open = false" x-cloak
                     class="absolute z-30 mt-1 w-full overflow-hidden rounded-md border bg-white shadow-lg"
                     style="border-color: var(--line)">
                    <div class="border-b p-2" style="border-color: var(--line); background: var(--surface-2)">
                        <input type="text" x-model="search" placeholder="Pesquisar profissional..."
                               class="w-full rounded border-0 bg-white px-2 py-1.5 text-sm shadow-sm"
                               style="border: 1px solid var(--line)">
                    </div>
                    <ul class="max-h-52 overflow-y-auto text-sm">
                        <template x-for="option in filteredOptions" :key="option.value">
                            <li @click="$wire.set('filtro_professional_id', option.value); open = false; search = ''"
                                class="cursor-pointer px-3 py-2 transition-colors hover:bg-gray-50"
                                :class="{ 'font-semibold': $wire.filtro_professional_id == option.value }"
                                :style="$wire.filtro_professional_id == option.value ? 'background: var(--accent-soft); color: var(--accent-strong)' : 'color: var(--ink)'">
                                <span x-text="option.label"></span>
                            </li>
                        </template>
                        <li x-show="filteredOptions.length === 0" class="px-3 py-2 text-center" style="color: var(--ink-3)">Nenhum encontrado</li>
                    </ul>
                </div>
            </div>

            <select wire:model.live="filtro_therapy_id" class="nd-select px-3 text-sm" style="width: 180px">
                <option value="">Todas as terapias</option>
                @foreach($terapiasFiltro as $terapia)
                    <option value="{{ $terapia->id }}">{{ $terapia->name }}</option>
                @endforeach
            </select>

            {{-- Convênio: multi-seleção — Unimed começa desmarcado (só entra como
                 visualização se a recepção marcar). Painel de checkbox, não <select
                 multiple> (péssima UX pra marcar/desmarcar poucos itens). --}}
            <div x-data="{ open: false }" class="relative">
                <button type="button" @click="open = !open" @click.away="open = false"
                        class="nd-btn-ghost inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-semibold transition-colors" style="min-height: 38px">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    Convênio
                    @if(count($filtro_agreement_ids) < $agreementsFiltro->count())
                        <span class="rounded-full px-1.5 text-[10px] font-bold text-white" style="background: var(--accent)">{{ count($filtro_agreement_ids) }}</span>
                    @endif
                </button>

                <div x-show="open" x-cloak @click.away="open = false"
                     class="absolute left-0 z-30 mt-2 w-56 overflow-hidden rounded-md border bg-white shadow-lg" style="border-color: var(--line)">
                    <div class="max-h-64 space-y-0.5 overflow-y-auto p-2 text-sm">
                        @foreach($agreementsFiltro as $agreement)
                            @php $ehUnimed = str_contains(mb_strtolower($agreement->name), 'unimed'); @endphp
                            <label class="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 hover:bg-gray-50">
                                <input type="checkbox" wire:model.live="filtro_agreement_ids" value="{{ $agreement->id }}"
                                       class="rounded border-gray-300 shadow-sm" style="accent-color: var(--accent)">
                                <span>{{ $agreement->name }}</span>
                                @if($ehUnimed)
                                    <span class="ml-auto text-[10px]" style="color: var(--ink-3)">visualização</span>
                                @endif
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            @if($filtro_patient_id || $filtro_professional_id || $filtro_therapy_id)
                <button type="button" wire:click="limparFiltros" class="text-sm font-semibold transition-colors hover:opacity-75" style="color: var(--danger)">
                    Limpar filtros
                </button>
            @endif
        </div>
    </div>

    {{-- Blocos de horário --}}
    @forelse($blocos as $faixa => $itens)
        <div class="mb-6">
            <div class="mb-2.5 flex items-center gap-3">
                <h3 class="text-sm font-bold uppercase tracking-wide" style="color: var(--ink-2)">{{ $faixa }}</h3>
                <div class="h-px flex-1" style="background: var(--line)"></div>
                <span class="nd-eyebrow">{{ $itens->count() }} {{ $itens->count() === 1 ? 'atendimento' : 'atendimentos' }}</span>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($itens as $item)
                    @php
                        $schedule = $item->schedule;
                        $corBorda = $item->isUnimed ? 'var(--line-2)' : match($item->status) {
                            'realizado' => 'var(--success)',
                            'falta' => 'var(--danger)',
                            default => 'var(--line-2)',
                        };
                    @endphp

                    <div class="nd-card flex flex-col gap-3 p-4" style="border-left: 3px solid {{ $corBorda }}; {{ $item->isUnimed ? 'opacity: .85' : '' }}">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="nd-num text-sm font-bold" style="color: var(--accent-strong)">
                                    {{ \Illuminate\Support\Str::substr($schedule->start_time, 0, 5) }} – {{ \Illuminate\Support\Str::substr($schedule->end_time, 0, 5) }}
                                </p>
                                <p class="mt-0.5 truncate text-sm font-bold" style="color: var(--ink)" title="{{ $schedule->patient?->name }}">
                                    {{ mb_strtoupper($schedule->patient?->name ?? 'Paciente removido') }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-1.5 text-xs">
                            <span class="rounded px-1.5 py-0.5 font-semibold" style="background: var(--accent-soft); color: var(--accent-strong)">
                                {{ $schedule->therapy?->name ?? 'N/A' }}
                            </span>
                            <span style="color: var(--ink-3)">·</span>
                            <span style="color: var(--ink-2)">{{ $schedule->serviceType?->name ?? 'Clínica' }}</span>
                            @if($item->isUnimed)
                                <span class="ml-auto rounded px-1.5 py-0.5 text-[10px] font-bold uppercase" style="background: var(--surface-2); color: var(--ink-3); border: 1px solid var(--line)">
                                    Unimed
                                </span>
                            @endif
                        </div>

                        <p class="truncate text-xs" style="color: var(--ink-2)" title="{{ $schedule->professional?->name }}">
                            <svg class="mr-1 inline h-3 w-3 -mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            {{ $schedule->professional?->name ?? 'Sem profissional' }}
                        </p>

                        <div class="mt-auto pt-1">
                            @if($item->isUnimed)
                                <div class="rounded-md px-3 py-2 text-center text-xs font-semibold" style="background: var(--surface-2); color: var(--ink-3); border: 1px dashed var(--line-2)" title="Consulte o relatório da Unimed para check-in/check-out e profissional">
                                    Atendimento será registrado de acordo com Relatório da UNIMED
                                </div>
                            @elseif($item->status === 'pendente')
                                <div class="flex gap-2">
                                    <button type="button" wire:click="abrirModalRealizar({{ $schedule->id }})"
                                            class="flex-1 rounded-md px-3 py-2 text-xs font-bold text-white transition-colors"
                                            style="background: var(--success)"
                                            onmouseover="this.style.background='var(--success-strong)'" onmouseout="this.style.background='var(--success)'">
                                        Sinalizar Realizada
                                    </button>
                                    <button type="button" wire:click="abrirModalFalta({{ $schedule->id }})"
                                            class="rounded-md px-3 py-2 text-xs font-bold transition-colors"
                                            style="background: var(--danger-soft); color: var(--danger-strong); border: 1px solid #fecaca">
                                        Falta
                                    </button>
                                </div>
                            @elseif($item->status === 'realizado')
                                <div class="flex items-center justify-between rounded-md px-3 py-2 text-xs font-bold" style="background: var(--success-soft); color: var(--success-strong)">
                                    <span class="flex items-center gap-1.5">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        Atendido {{ $item->appointment ? \Illuminate\Support\Str::substr($item->appointment->check_in, 0, 5) . '–' . \Illuminate\Support\Str::substr($item->appointment->check_out, 0, 5) : '' }}
                                    </span>
                                    @if($item->appointment)
                                        <a href="{{ route('terapias-realizadas.edit', $item->appointment->id) }}" wire:navigate class="underline decoration-dotted hover:opacity-75">editar</a>
                                    @endif
                                </div>
                            @else
                                <div class="rounded-md px-3 py-2 text-xs font-bold" style="background: var(--danger-soft); color: var(--danger-strong)"
                                     title="{{ $item->falta?->observacao }}">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        Falta · {{ $item->falta?->motivoLabel() }}
                                    </div>
                                    @if($item->falta?->registeredBy)
                                        <p class="mt-0.5 text-[10px] font-normal" style="color: var(--danger-strong); opacity: .75">
                                            registrada por {{ $item->falta->registeredBy->name }}
                                        </p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="nd-card flex flex-col items-center justify-center gap-2 py-16 text-center">
            <svg class="h-9 w-9" style="color: var(--ink-3)" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <p class="text-sm font-semibold" style="color: var(--ink)">Nenhum horário na grade para este dia</p>
            <p class="text-sm" style="color: var(--ink-3)">Confira a Agenda de Profissionais se esperava ver algo aqui.</p>
        </div>
    @endforelse

    {{-- Modal: Sinalizar Realizada --}}
    <div x-data="{ open: @entangle('isModalRealizarOpen') }" x-show="open" x-cloak
         @keydown.escape.window="open && $wire.fecharModalRealizar()"
         class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
        <div x-show="open" x-transition.opacity @click="$wire.fecharModalRealizar()" class="absolute inset-0 bg-gray-900/50"></div>

        <div x-show="open" x-transition:enter="ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="nd-card relative w-full max-w-md overflow-hidden bg-white text-left shadow-xl">
            <form wire:submit.prevent="salvarRealizado">
                <div class="p-6">
                    <div class="mb-4 flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full" style="background: var(--success-soft)">
                            <svg class="h-5 w-5" style="color: var(--success-strong)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <div>
                            <h3 class="text-base nd-title">Sinalizar atendimento realizado</h3>
                            <p class="mt-0.5 text-sm" style="color: var(--ink-2)">Confirme os horários — já vêm da grade, só ajuste se foi diferente.</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium" style="color: var(--ink)">Profissional</label>
                            <select wire:model="professional_id" class="nd-select px-3 text-sm">
                                @foreach($profissionaisDaTerapia as $prof)
                                    <option value="{{ $prof->id }}">{{ $prof->name }}</option>
                                @endforeach
                            </select>
                            @error('professional_id') <span class="mt-1 block text-xs" style="color: var(--danger)">{{ $message }}</span> @enderror
                            <p class="mt-1 text-xs" style="color: var(--ink-3)">Diferente do que está na grade? Selecione quem realmente atendeu.</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1 block text-sm font-medium" style="color: var(--ink)">Início</label>
                                <input type="time" wire:model="check_in" class="nd-input px-3 text-sm">
                                @error('check_in') <span class="mt-1 block text-xs" style="color: var(--danger)">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium" style="color: var(--ink)">Término</label>
                                <input type="time" wire:model="check_out" class="nd-input px-3 text-sm">
                                @error('check_out') <span class="mt-1 block text-xs" style="color: var(--danger)">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium" style="color: var(--ink)">Guia <span class="font-normal" style="color: var(--ink-3)">(opcional)</span></label>
                            <input type="text" wire:model="guide" class="nd-input px-3 text-sm" placeholder="Número da guia, se já tiver">
                        </div>
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-2 border-t p-4 sm:flex-row sm:justify-end" style="border-color: var(--line); background: var(--surface-2)">
                    <button type="button" wire:click="fecharModalRealizar" class="nd-btn-ghost rounded-md px-4 py-2 text-sm font-semibold transition-colors">Cancelar</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="salvarRealizado"
                            class="rounded-md px-4 py-2 text-sm font-semibold text-white transition-colors disabled:opacity-60" style="background: var(--success)">
                        <span wire:loading.remove wire:target="salvarRealizado">Confirmar Atendimento</span>
                        <span wire:loading wire:target="salvarRealizado">Salvando...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: Registrar Falta --}}
    <div x-data="{ open: @entangle('isModalFaltaOpen') }" x-show="open" x-cloak
         @keydown.escape.window="open && $wire.fecharModalFalta()"
         class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
        <div x-show="open" x-transition.opacity @click="$wire.fecharModalFalta()" class="absolute inset-0 bg-gray-900/50"></div>

        <div x-show="open" x-transition:enter="ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="nd-card relative w-full max-w-md overflow-hidden bg-white text-left shadow-xl">
            <form wire:submit.prevent="salvarFalta">
                <div class="p-6">
                    <div class="mb-4 flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full" style="background: var(--danger-soft)">
                            <svg class="h-5 w-5" style="color: var(--danger-strong)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-base nd-title">Registrar falta</h3>
                            <p class="mt-0.5 text-sm" style="color: var(--ink-2)">Fica registrado o motivo e a data</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium" style="color: var(--ink)">Motivo</label>
                            <select wire:model="motivo" class="nd-select px-3 text-sm">
                                <option value="">Selecione...</option>
                                @foreach($motivoOptions as $valor => $rotulo)
                                    <option value="{{ $valor }}">{{ $rotulo }}</option>
                                @endforeach
                            </select>
                            @error('motivo') <span class="mt-1 block text-xs" style="color: var(--danger)">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium" style="color: var(--ink)">Observação <span class="font-normal" style="color: var(--ink-3)">(opcional)</span></label>
                            <textarea wire:model="observacao" rows="2" class="nd-input px-3 py-2 text-sm" placeholder="Algum detalhe a mais, se precisar"></textarea>
                            @error('observacao') <span class="mt-1 block text-xs" style="color: var(--danger)">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-2 border-t p-4 sm:flex-row sm:justify-end" style="border-color: var(--line); background: var(--surface-2)">
                    <button type="button" wire:click="fecharModalFalta" class="nd-btn-ghost rounded-md px-4 py-2 text-sm font-semibold transition-colors">Cancelar</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="salvarFalta"
                            class="rounded-md px-4 py-2 text-sm font-semibold text-white transition-colors disabled:opacity-60" style="background: var(--danger)">
                        <span wire:loading.remove wire:target="salvarFalta">Registrar Falta</span>
                        <span wire:loading wire:target="salvarFalta">Salvando...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
