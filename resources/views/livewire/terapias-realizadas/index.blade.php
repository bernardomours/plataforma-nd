@php
    // Coordenador/supervisor não fica mais preso ao próprio nome no filtro de
    // Profissional — a trava dele agora é por paciente vinculado (patientIdsVinculados),
    // então o select de Profissional volta a ficar livre pra ele também.
    $isCoordenadorOuSupervisor = auth()->user()->hasAnyRole(['coordinator', 'supervisor'])
        && ! auth()->user()->hasAnyRole(['admin', 'manager', 'administrative']);
    $isProfDisabled = !auth()->user()->hasAnyRole(['admin', 'manager', 'administrative']) && ! $isCoordenadorOuSupervisor;
@endphp

<div class="nd-ui">

    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="nd-eyebrow">Atendimentos</p>
            <h1 class="mt-1 text-2xl nd-title">Terapias Realizadas</h1>
            <p class="mt-1 text-sm" style="color: var(--ink-2)">Registro de todos os atendimentos da clínica.</p>
        </div>

        @hasanyrole('admin|manager|administrative')
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('terapias-realizadas.create') }}" wire:navigate
                   class="nd-btn-primary inline-flex items-center justify-center gap-2 rounded-md px-4 py-2.5 text-sm font-semibold text-white transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Registrar atendimento
                </a>

                @hasanyrole('admin|manager')
                    <button type="button" wire:click.prevent="exportPdf" wire:loading.attr="disabled" wire:target="exportPdf"
                            class="nd-btn-ghost inline-flex items-center justify-center gap-2 rounded-md px-4 py-2.5 text-sm font-semibold transition-colors disabled:opacity-60">
                        <svg wire:loading.remove wire:target="exportPdf" class="h-4 w-4" style="color: var(--danger)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <svg wire:loading wire:target="exportPdf" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                            <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="exportPdf">PDF</span>
                        <span wire:loading wire:target="exportPdf">Gerando</span>
                    </button>

                    <button type="button" wire:click.prevent="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel"
                            class="nd-btn-ghost inline-flex items-center justify-center gap-2 rounded-md px-4 py-2.5 text-sm font-semibold transition-colors disabled:opacity-60">
                        <svg wire:loading.remove wire:target="exportExcel" class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                        <svg wire:loading wire:target="exportExcel" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                            <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="exportExcel">Excel</span>
                        <span wire:loading wire:target="exportExcel">Gerando</span>
                    </button>

                    <button type="button" wire:click="$set('showImportModal', true)"
                            class="nd-btn-ghost inline-flex items-center justify-center gap-2 rounded-md px-4 py-2.5 text-sm font-semibold transition-colors">
                        <svg class="h-4 w-4" style="color: var(--accent)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        Importar CSV
                    </button>
                @endhasanyrole
            </div>
        @endhasanyrole
    </div>

    {{-- KPIs --}}
    <div class="mb-6 grid grid-cols-2 gap-3 sm:max-w-sm">
        <div class="nd-card flex items-center gap-3 p-4">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md" style="background: var(--accent-soft)">
                <svg class="h-5 w-5" style="color: var(--accent-strong)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="nd-eyebrow">Atendimentos</p>
                <p class="text-2xl font-bold nd-num leading-tight" style="color: var(--ink)">{{ number_format($totalConsultas, 0, ',', '.') }}</p>
            </div>
        </div>
        <div class="nd-card flex items-center gap-3 p-4">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-emerald-50">
                <svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="nd-eyebrow">Sessões</p>
                <p class="text-2xl font-bold nd-num leading-tight" style="color: var(--ink)">{{ number_format($totalSessoes ?? 0, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="nd-card mb-6">
        <div class="flex items-center justify-between border-b p-4" style="border-color: var(--line)">
            <h3 class="text-sm nd-title">Filtros</h3>
            <button type="button" wire:click="clearFilters" class="text-sm font-semibold transition-colors hover:opacity-75" style="color: var(--danger)">
                Limpar filtros
            </button>
        </div>

        @if ($patientIdsVinculados !== null)
            <div class="flex items-start gap-2 border-b p-4 text-sm" style="border-color: var(--line); background: var(--accent-soft); color: var(--accent-strong)">
                <svg class="h-4 w-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Mostrando apenas as crianças vinculadas a você como coordenador ou supervisor (ver Vínculos de Pacientes).</span>
            </div>
        @endif

        <div class="space-y-4 p-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">

                {{-- Paciente (combobox com pesquisa) --}}
                <div>
                    <label class="nd-eyebrow mb-1.5 block">Paciente</label>
                    <div x-data="{
                            open: false,
                            search: '',
                            options: [
                                { value: '', label: 'Todos' },
                                @foreach($patients as $patient)
                                    { value: '{{ $patient->id }}', label: '{!! addslashes($patient->name) !!}' },
                                @endforeach
                            ],
                            get filteredOptions() {
                                if (this.search === '') return this.options;
                                return this.options.filter(i => i.label.toLowerCase().includes(this.search.toLowerCase()));
                            },
                            get selectedLabel() {
                                let selectedOpt = this.options.find(i => i.value == $wire.patient_id);
                                return selectedOpt ? selectedOpt.label : 'Todos';
                            }
                        }"
                        class="relative w-full"
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
                                    <li @click="$wire.set('patient_id', option.value); open = false; search = ''"
                                        class="cursor-pointer px-3 py-2 transition-colors hover:bg-gray-50"
                                        :class="{ 'font-semibold': $wire.patient_id == option.value }"
                                        :style="$wire.patient_id == option.value ? 'background: var(--accent-soft); color: var(--accent-strong)' : 'color: var(--ink)'">
                                        <span x-text="option.label"></span>
                                    </li>
                                </template>
                                <li x-show="filteredOptions.length === 0" class="px-3 py-2 text-center" style="color: var(--ink-3)">Nenhum encontrado</li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Profissional (combobox com pesquisa e bloqueio por papel) --}}
                <div>
                    <label class="nd-eyebrow mb-1.5 block">Profissional</label>
                    <div x-data="{
                            open: false,
                            search: '',
                            isDisabled: {{ $isProfDisabled ? 'true' : 'false' }},
                            options: [
                                @hasanyrole('admin|manager|administrative')
                                    { value: '', label: 'Todos' },
                                @endhasanyrole
                                @foreach($professionals as $professional)
                                    { value: '{{ $professional->id }}', label: '{!! addslashes($professional->name) !!}' },
                                @endforeach
                            ],
                            get filteredOptions() {
                                if (this.search === '') return this.options;
                                return this.options.filter(i => i.label.toLowerCase().includes(this.search.toLowerCase()));
                            },
                            get selectedLabel() {
                                let selectedOpt = this.options.find(i => i.value == $wire.professional_id);
                                return selectedOpt ? selectedOpt.label : '{{ $isProfDisabled ? ($professionals->first()->name ?? 'Fixo') : 'Todos' }}';
                            }
                        }"
                        class="relative w-full"
                    >
                        <button type="button" @click="if (!isDisabled) open = !open"
                                :class="isDisabled ? 'cursor-not-allowed opacity-60' : 'cursor-pointer'"
                                class="flex min-h-[38px] w-full items-center justify-between gap-2 rounded-md border bg-white px-3 py-2 text-left text-sm transition-colors"
                                style="border-color: var(--line)">
                            <span x-text="selectedLabel" class="truncate" style="color: var(--ink)"></span>
                            <svg x-show="!isDisabled" class="h-4 w-4 shrink-0 transition-transform" :class="{ 'rotate-180': open }" style="color: var(--ink-3)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
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
                                    <li @click="$wire.set('professional_id', option.value); open = false; search = ''"
                                        class="cursor-pointer px-3 py-2 transition-colors hover:bg-gray-50"
                                        :class="{ 'font-semibold': $wire.professional_id == option.value }"
                                        :style="$wire.professional_id == option.value ? 'background: var(--accent-soft); color: var(--accent-strong)' : 'color: var(--ink)'">
                                        <span x-text="option.label"></span>
                                    </li>
                                </template>
                                <li x-show="filteredOptions.length === 0" class="px-3 py-2 text-center" style="color: var(--ink-3)">Nenhum encontrado</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="filtro-convenio" class="nd-eyebrow mb-1.5 block">Convênio</label>
                    <select id="filtro-convenio" wire:model="agreement_id" class="nd-select block w-full px-3 text-sm">
                        <option value="">Todos</option>
                        @foreach($agreements as $agreement)
                            <option value="{{ $agreement->id }}">{{ $agreement->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="filtro-terapia" class="nd-eyebrow mb-1.5 block">Terapia</label>
                    <select id="filtro-terapia" wire:model="therapy_id" class="nd-select block w-full px-3 text-sm">
                        <option value="">Todos</option>
                        @foreach($therapies as $therapy)
                            <option value="{{ $therapy->id }}">{{ $therapy->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="filtro-tipo" class="nd-eyebrow mb-1.5 block">Tipo de Atendimento</label>
                    <select id="filtro-tipo" wire:model="service_type_id" class="nd-select block w-full px-3 text-sm">
                        <option value="">Todos</option>
                        @foreach($serviceTypes as $serviceType)
                            <option value="{{ $serviceType->id }}">{{ $serviceType->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <div>
                    <label for="filtro-unidade" class="nd-eyebrow mb-1.5 block">Unidade</label>
                    <select id="filtro-unidade" wire:model="unit_id" class="nd-select block w-full px-3 text-sm">
                        <option value="">Todos</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->city ?? $unit->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="filtro-guia" class="nd-eyebrow mb-1.5 block">Número da Guia</label>
                    <input id="filtro-guia" type="text" wire:model="guide" placeholder="Digite para pesquisar..."
                           class="nd-input block w-full px-3 text-sm">
                </div>
                <div>
                    <label for="filtro-data-inicio" class="nd-eyebrow mb-1.5 block">Data Início</label>
                    <input id="filtro-data-inicio" type="date" wire:model="start_date" class="nd-input block w-full px-3 text-sm">
                </div>
                <div>
                    <label for="filtro-data-fim" class="nd-eyebrow mb-1.5 block">Data Fim</label>
                    <input id="filtro-data-fim" type="date" wire:model="end_date" class="nd-input block w-full px-3 text-sm">
                </div>
                <div class="flex items-end">
                    <button type="button" wire:click="applyFilters" wire:loading.attr="disabled" wire:target="applyFilters"
                            class="nd-btn-primary w-full rounded-md px-4 py-2 text-sm font-semibold text-white transition-colors disabled:opacity-60">
                        Aplicar filtros
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabela --}}
    <div class="nd-card overflow-hidden"
         wire:loading.class="opacity-60"
         wire:target="applyFilters,clearFilters,search,selectedColumns">

        <div class="flex flex-col gap-3 border-b p-4 sm:flex-row sm:items-center sm:justify-between" style="border-color: var(--line)">
            <div class="relative w-full sm:w-72">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-4 w-4" style="color: var(--ink-3)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                    </svg>
                </span>
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="Pesquisar paciente..."
                       class="nd-input block w-full pl-9 pr-3 text-sm">
            </div>

            <div x-data="{ open: false }" class="relative self-end sm:self-auto">
                <button type="button" @click="open = !open" @click.away="open = false"
                        class="nd-btn-ghost inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-semibold transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                    </svg>
                    Colunas
                </button>

                <div x-show="open" x-cloak
                     class="absolute right-0 z-30 mt-2 w-60 overflow-hidden rounded-md border bg-white shadow-lg"
                     style="border-color: var(--line)">
                    <div class="flex items-center justify-between border-b p-3" style="border-color: var(--line)">
                        <span class="text-sm nd-title">Colunas visíveis</span>
                        <button wire:click="resetColumns" class="text-xs font-semibold transition-colors hover:opacity-75" style="color: var(--danger)">Redefinir</button>
                    </div>

                    <div class="max-h-64 space-y-0.5 overflow-y-auto p-2 text-sm">
                        <label class="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 hover:bg-gray-50">
                            <input type="checkbox" wire:model.live="selectedColumns.nome" class="rounded border-gray-300 shadow-sm" style="accent-color: var(--accent)"> Nome
                        </label>
                        <label class="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 hover:bg-gray-50">
                            <input type="checkbox" wire:model.live="selectedColumns.data" class="rounded border-gray-300 shadow-sm" style="accent-color: var(--accent)"> Data
                        </label>
                        <label class="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 hover:bg-gray-50">
                            <input type="checkbox" wire:model.live="selectedColumns.guia" class="rounded border-gray-300 shadow-sm" style="accent-color: var(--accent)"> Guia
                        </label>
                        <label class="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 hover:bg-gray-50">
                            <input type="checkbox" wire:model.live="selectedColumns.terapia" class="rounded border-gray-300 shadow-sm" style="accent-color: var(--accent)"> Terapia
                        </label>
                        <label class="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 hover:bg-gray-50">
                            <input type="checkbox" wire:model.live="selectedColumns.tipo_atendimento" class="rounded border-gray-300 shadow-sm" style="accent-color: var(--accent)"> Tipo de Atendimento
                        </label>
                        <label class="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 hover:bg-gray-50">
                            <input type="checkbox" wire:model.live="selectedColumns.check_in" class="rounded border-gray-300 shadow-sm" style="accent-color: var(--accent)"> Check-in
                        </label>
                        <label class="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 hover:bg-gray-50">
                            <input type="checkbox" wire:model.live="selectedColumns.check_out" class="rounded border-gray-300 shadow-sm" style="accent-color: var(--accent)"> Check-out
                        </label>
                        <label class="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 hover:bg-gray-50">
                            <input type="checkbox" wire:model.live="selectedColumns.qtd_sessoes" class="rounded border-gray-300 shadow-sm" style="accent-color: var(--accent)"> Qtd de Sessões
                        </label>
                        <label class="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 hover:bg-gray-50">
                            <input type="checkbox" wire:model.live="selectedColumns.profissional" class="rounded border-gray-300 shadow-sm" style="accent-color: var(--accent)"> Profissional
                        </label>
                        <label class="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 hover:bg-gray-50">
                            <input type="checkbox" wire:model.live="selectedColumns.registrado_em" class="rounded border-gray-300 shadow-sm" style="accent-color: var(--accent)"> Registrado em
                        </label>
                        <label class="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 hover:bg-gray-50">
                            <input type="checkbox" wire:model.live="selectedColumns.atualizado_em" class="rounded border-gray-300 shadow-sm" style="accent-color: var(--accent)"> Atualizado em
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left text-sm">
                <thead>
                    <tr class="border-b text-xs font-bold uppercase tracking-wider" style="border-color: var(--line); color: var(--ink-3)">
                        @if($selectedColumns['nome']) <th class="whitespace-nowrap px-4 py-3">Nome</th> @endif
                        @if($selectedColumns['data']) <th class="whitespace-nowrap px-4 py-3">Data</th> @endif
                        @if($selectedColumns['guia']) <th class="whitespace-nowrap px-4 py-3">Guia</th> @endif
                        @if($selectedColumns['terapia']) <th class="whitespace-nowrap px-4 py-3">Terapia</th> @endif
                        @if($selectedColumns['tipo_atendimento']) <th class="whitespace-nowrap px-4 py-3">Tipo de Atendimento</th> @endif
                        @if($selectedColumns['check_in']) <th class="whitespace-nowrap px-4 py-3">Check-in</th> @endif
                        @if($selectedColumns['check_out']) <th class="whitespace-nowrap px-4 py-3">Check-out</th> @endif
                        @if($selectedColumns['qtd_sessoes']) <th class="whitespace-nowrap px-4 py-3 text-right">Sessões</th> @endif
                        @if($selectedColumns['profissional']) <th class="whitespace-nowrap px-4 py-3">Profissional</th> @endif
                        @if($selectedColumns['registrado_em']) <th class="whitespace-nowrap px-4 py-3">Registrado em</th> @endif
                        @if($selectedColumns['atualizado_em']) <th class="whitespace-nowrap px-4 py-3">Atualizado em</th> @endif

                        @hasanyrole('admin|manager|administrative')
                            <th class="whitespace-nowrap px-4 py-3 text-right">Ações</th>
                        @endhasanyrole
                    </tr>
                </thead>
                <tbody class="divide-y" style="--tw-divide-opacity: 1; border-color: var(--line)">
                    @forelse ($appointments as $appointment)
                        @php
                            // Mesma sinalização de "Auditoria de Atendimentos" (Producao\AtendimentosRealizados),
                            // pra profissional ter noção do que está certo/errado no próprio histórico. Usa a
                            // regra canônica de duração (CLAUDE.md / Create|Edit::calculateSessions()): Humana
                            // sempre 40min; ABA fora da Humana, 60min; demais terapias, 40min.
                            $semCheckout = ! $appointment->check_out;
                            $duracaoInsuficiente = false;
                            $motivoAlerta = '';

                            if ($semCheckout) {
                                $motivoAlerta = 'Sem check-out registrado.';
                            } elseif ($appointment->check_in) {
                                $inicioAtend = \Carbon\Carbon::parse($appointment->check_in);
                                $fimAtend = \Carbon\Carbon::parse($appointment->check_out);

                                if ($fimAtend->greaterThan($inicioAtend)) {
                                    $minutosReais = (int) $inicioAtend->diffInMinutes($fimAtend);
                                    $qtdSessoesAtend = $appointment->session_number ?? 1;

                                    $nomeConvenioAtend = $appointment->agreement?->name ?? $appointment->patient?->agreement?->name;
                                    $isHumanaAtend = $nomeConvenioAtend === 'Humana';
                                    $isAbaAtend = $appointment->therapy?->name === 'ABA';
                                    $duracaoIdealPorSessao = $isHumanaAtend ? 40 : ($isAbaAtend ? 60 : 40);

                                    $minutosMinimosEsperados = max(0, ($qtdSessoesAtend * $duracaoIdealPorSessao) - 10);

                                    if ($minutosReais < $minutosMinimosEsperados) {
                                        $duracaoInsuficiente = true;
                                        $motivoAlerta = sprintf(
                                            'Duração de %02d:%02d registrada para %d sessão(ões) — mínimo esperado %02d:%02d.',
                                            intdiv($minutosReais, 60), $minutosReais % 60,
                                            $qtdSessoesAtend,
                                            intdiv($minutosMinimosEsperados, 60), $minutosMinimosEsperados % 60
                                        );
                                    }
                                }
                            }

                            $temAlerta = $semCheckout || $duracaoInsuficiente;
                        @endphp
                        <tr class="whitespace-nowrap transition-colors {{ $temAlerta ? '' : 'hover:bg-gray-50' }}"
                            style="border-color: var(--line); {{ $temAlerta ? 'background: var(--danger-soft);' : '' }}"
                            @if($temAlerta) title="{{ $motivoAlerta }}" @endif>
                            @if($selectedColumns['nome'])
                                <td class="px-4 py-3 font-medium" style="color: var(--ink)">
                                    <div class="flex items-center gap-2">
                                        @if($temAlerta)
                                            <span class="inline-flex shrink-0 items-center gap-1 rounded px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                                                  style="background: var(--danger-soft); color: var(--danger-strong); border: 1px solid #fecaca"
                                                  title="{{ $motivoAlerta }}">
                                                <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                                                </svg>
                                                {{ $semCheckout ? 'Sem check-out' : 'Tempo Insuficiente' }}
                                            </span>
                                        @endif
                                        <span>{{ $appointment->patient?->name }}</span>
                                    </div>
                                </td>
                            @endif
                            @if($selectedColumns['data']) <td class="px-4 py-3" style="color: var(--ink-2)">{{ $appointment->appointment_date ? \Carbon\Carbon::parse($appointment->appointment_date)->format('d/m/Y') : '-' }}</td> @endif
                            @if($selectedColumns['guia']) <td class="px-4 py-3 nd-num" style="color: var(--ink-2)">{{ $appointment->guide ?? '-' }}</td> @endif
                            @if($selectedColumns['terapia']) <td class="px-4 py-3" style="color: var(--ink-2)">{{ $appointment->therapy?->name }}</td> @endif
                            @if($selectedColumns['tipo_atendimento']) <td class="px-4 py-3" style="color: var(--ink-2)">{{ $appointment->serviceType?->name ?? '-' }}</td> @endif
                            @if($selectedColumns['check_in']) <td class="px-4 py-3 nd-num" style="color: var(--ink-2)">{{ $appointment->check_in ? \Carbon\Carbon::parse($appointment->check_in)->format('H:i:s') : '-' }}</td> @endif
                            @if($selectedColumns['check_out']) <td class="px-4 py-3 nd-num" style="color: var(--ink-2)">{{ $appointment->check_out ? \Carbon\Carbon::parse($appointment->check_out)->format('H:i:s') : '-' }}</td> @endif
                            @if($selectedColumns['qtd_sessoes']) <td class="px-4 py-3 text-right font-semibold nd-num" style="color: var(--ink)">{{ $appointment->session_number ?? 1 }}</td> @endif
                            @if($selectedColumns['profissional']) <td class="px-4 py-3" style="color: var(--ink-2)">{{ $appointment->professional?->name ?? '-' }}</td> @endif
                            @if($selectedColumns['registrado_em']) <td class="px-4 py-3 text-xs" style="color: var(--ink-3)">{{ $appointment->created_at ? $appointment->created_at->format('d/m/Y H:i') : '-' }}</td> @endif
                            @if($selectedColumns['atualizado_em']) <td class="px-4 py-3 text-xs" style="color: var(--ink-3)">{{ $appointment->updated_at ? $appointment->updated_at->format('d/m/Y H:i') : '-' }}</td> @endif

                            @hasanyrole('admin|manager|administrative')
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('terapias-realizadas.edit', $appointment->id) }}" wire:navigate
                                           class="text-xs font-semibold transition-colors" style="color: var(--accent)">
                                            Editar
                                        </a>
                                        <button type="button"
                                                wire:click="deleteAppointment({{ $appointment->id }})"
                                                wire:confirm="Tem certeza que deseja excluir este atendimento? Essa ação não pode ser desfeita."
                                                class="text-xs font-semibold transition-colors" style="color: var(--danger)">
                                            Excluir
                                        </button>
                                    </div>
                                </td>
                            @endhasanyrole
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="px-4 py-16 text-center">
                                <svg class="mx-auto h-9 w-9" style="color: var(--ink-3)" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <p class="mt-3 text-sm font-semibold" style="color: var(--ink)">Nenhum atendimento encontrado</p>
                                <p class="mt-1 text-sm" style="color: var(--ink-3)">Ajuste os filtros ou registre um novo atendimento.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t px-4 py-3" style="border-color: var(--line)">
            {{ $appointments->links() }}
        </div>
    </div>

    {{-- Modal de importação --}}
    <div x-data="{ show: @entangle('showImportModal') }" x-show="show" x-cloak
         @keydown.escape.window="show = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="import-modal-title">

        <div x-show="show" x-transition.opacity @click="show = false" class="absolute inset-0 bg-gray-900/50"></div>

        <div x-show="show"
             x-transition:enter="ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="ease-in duration-100" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
             class="nd-card relative w-full max-w-lg overflow-hidden bg-white text-left shadow-xl">

            <form wire:submit.prevent="processImport">
                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full" style="background: var(--accent-soft)">
                            <svg class="h-5 w-5" style="color: var(--accent-strong)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 id="import-modal-title" class="text-base nd-title">Importar CSV Unimed</h3>
                            <p class="mt-0.5 text-sm" style="color: var(--ink-2)">O convênio e a unidade dos atendimentos são resolvidos pelo relatório selecionado.</p>

                            <div class="mt-4 space-y-4">
                                <div>
                                    <label for="unidade-relatorio" class="mb-1 block text-sm font-medium" style="color: var(--ink)">
                                        Unidade do Relatório <span style="color: var(--danger)">*</span>
                                    </label>
                                    <select id="unidade-relatorio" wire:model="unidade_relatorio" class="nd-select block w-full px-3 text-sm" required>
                                        <option value="">Selecione...</option>
                                        <option value="Mossoró">Mossoró</option>
                                        <option value="Natal">Natal</option>
                                        <option value="João Câmara">João Câmara</option>
                                    </select>
                                    @error('unidade_relatorio') <span class="mt-1 block text-xs" style="color: var(--danger)">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label for="arquivo-csv" class="mb-1 block text-sm font-medium" style="color: var(--ink)">
                                        Arquivo CSV <span style="color: var(--danger)">*</span>
                                    </label>
                                    <input id="arquivo-csv" type="file" wire:model="arquivo_csv" accept=".csv"
                                           class="block w-full text-sm file:mr-4 file:rounded-md file:border-0 file:bg-[var(--accent-soft)] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-[var(--accent-strong)] hover:file:bg-[var(--accent-soft)]"
                                           style="color: var(--ink-2)" required>
                                    @error('arquivo_csv') <span class="mt-1 block text-xs" style="color: var(--danger)">{{ $message }}</span> @enderror
                                </div>

                                @if (session()->has('success'))
                                    <div class="rounded-md border p-3 text-sm" style="background: #f0fdf4; border-color: #bbf7d0; color: #166534">
                                        {{ session('success') }}
                                    </div>
                                @endif
                                @if (session()->has('warning'))
                                    <div class="rounded-md border p-3 text-sm font-semibold" style="background: #fffbeb; border-color: #fde68a; color: #92400e">
                                        {{ session('warning') }}
                                    </div>
                                @endif

                                @if (!empty($importMessages))
                                    <div class="max-h-40 space-y-1 overflow-y-auto rounded-md border p-3 text-xs"
                                         style="background: var(--danger-soft); border-color: #fecaca; color: var(--danger-strong)">
                                        <p class="mb-2 font-semibold">Exibindo os primeiros erros:</p>
                                        @foreach(array_slice($importMessages, 0, 10) as $msg)
                                            <p>{!! $msg !!}</p>
                                        @endforeach
                                        @if(count($importMessages) > 10)
                                            <p class="mt-2 italic">e mais {{ count($importMessages) - 10 }} erros ocultados.</p>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-2 border-t p-4 sm:flex-row sm:justify-end" style="border-color: var(--line); background: var(--surface-2)">
                    <button type="button" @click="show = false"
                            class="nd-btn-ghost rounded-md px-4 py-2 text-sm font-semibold transition-colors">
                        Fechar
                    </button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="processImport"
                            class="nd-btn-primary rounded-md px-4 py-2 text-sm font-semibold text-white transition-colors disabled:opacity-60">
                        <span wire:loading.remove wire:target="processImport">Processar importação</span>
                        <span wire:loading wire:target="processImport">Processando...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
