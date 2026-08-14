<div>
    <x-slot name="header">
        <div class="flex items-center gap-4 mt-4">
            <a href="{{ route('terapias-realizadas.index') }}" wire:navigate class="p-2 -ml-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-full transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            
            <div class="flex flex-col">
                <nav class="flex text-xs text-gray-500 mb-1" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1">
                        <li><a href="{{ route('terapias-realizadas.index') }}" wire:navigate class="hover:text-blue-600 transition-colors">Terapias Realizadas</a></li>
                        <li><span class="mx-1 text-gray-400">/</span></li>
                        <li aria-current="page" class="text-gray-700 font-medium">Registrar Consulta</li>
                    </ol>
                </nav>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Registrar Consulta</h2>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <form wire:submit.prevent="save" class="bg-white shadow-sm sm:rounded-lg border border-gray-200 p-6 space-y-6">
            
            @if (session()->has('message'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" 
                    class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg relative flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span class="font-medium text-sm">{{ session('message') }}</span>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Paciente <span class="text-red-500">*</span></label>
                    <div wire:ignore x-data="{
                        tom: null,
                        init() {
                            this.tom = new TomSelect(this.$refs.select, { create: false });
                            this.tom.on('change', (val) => { $wire.set('patient_id', val); });
                            this.$watch('$wire.patient_id', (val) => {
                                if (this.tom.getValue() !== val) this.tom.setValue(val, true);
                            });
                        }
                    }">
                        <select x-ref="select" placeholder="Selecione o Paciente" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Selecione o Paciente</option>
                            @foreach($patients as $patient)
                                <option value="{{ $patient->id }}">{{ $patient->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @error('patient_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Terapia <span class="text-red-500">*</span></label>
                    <div wire:ignore x-data="{
                        tom: null,
                        init() {
                            this.tom = new TomSelect(this.$refs.select, { create: false });
                            this.tom.on('change', (val) => { $wire.set('therapy_id', val); });
                            this.$watch('$wire.therapy_id', (val) => {
                                if (this.tom.getValue() !== val) this.tom.setValue(val, true);
                            });
                        }
                    }">
                        <select x-ref="select" placeholder="Selecione a Terapia" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Selecione a Terapia</option>
                            @foreach($therapies as $therapy)
                                <option value="{{ $therapy->id }}">{{ $therapy->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @error('therapy_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Profissional <span class="text-red-500">*</span></label>
                    <div wire:key="prof-select-{{ $therapy_id ?? 'empty' }}">
                        <div wire:ignore x-data="{
                            tom: null,
                            init() {
                                this.tom = new TomSelect(this.$refs.select, { create: false });
                                this.tom.on('change', (val) => { $wire.set('professional_id', val); });
                                this.$watch('$wire.professional_id', (val) => {
                                    if (this.tom.getValue() !== val) this.tom.setValue(val, true);
                                });
                            }
                        }">
                            <select x-ref="select" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" {{ empty($therapy_id) ? 'disabled' : '' }} placeholder="{{ empty($therapy_id) ? 'Selecione a Terapia primeiro' : 'Selecione o Profissional' }}">
                                <option value="">{{ empty($therapy_id) ? 'Selecione a Terapia primeiro' : 'Selecione o Profissional' }}</option>
                                @foreach($professionals as $professional)
                                    <option value="{{ $professional->id }}">{{ $professional->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @error('professional_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Atendimento <span class="text-red-500">*</span></label>
                    <div wire:ignore x-data="{
                        tom: null,
                        init() {
                            this.tom = new TomSelect(this.$refs.select, { create: false });
                            this.tom.on('change', (val) => { $wire.set('service_type_id', val); });
                            this.$watch('$wire.service_type_id', (val) => {
                                if (this.tom.getValue() !== val) this.tom.setValue(val, true);
                            });
                        }
                    }">
                        <select x-ref="select" placeholder="Selecione o Tipo" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Selecione o Tipo</option>
                            @foreach($serviceTypes as $serviceType)
                                <option value="{{ $serviceType->id }}">{{ $serviceType->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @error('service_type_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <hr class="border-gray-200">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50 p-4 rounded-xl border border-gray-100">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Atalho de Data</label>
                    <div class="inline-flex p-1 bg-gray-200 rounded-lg shadow-inner">
                        <button type="button" 
                            wire:click="$set('data_rapida', 'ontem')"
                            class="px-4 py-1.5 text-xs font-bold rounded-md transition-all {{ $data_rapida === 'ontem' ? 'bg-yellow-500 text-white shadow-sm' : 'text-gray-600 hover:text-gray-800' }}">
                            Ontem
                        </button>

                        <button type="button" 
                            wire:click="$set('data_rapida', 'hoje')"
                            class="px-4 py-1.5 text-xs font-bold rounded-md transition-all {{ $data_rapida === 'hoje' ? 'bg-green-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-800' }}">
                            Hoje
                        </button>

                        <button type="button" 
                            wire:click="$set('data_rapida', 'outro')"
                            class="px-4 py-1.5 text-xs font-bold rounded-md transition-all {{ $data_rapida === 'outro' ? 'bg-gray-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-800' }}">
                            Outra Data
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Data da Consulta <span class="text-red-500">*</span></label>
                    <input type="date" wire:model="appointment_date" 
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 {{ $data_rapida !== 'outro' ? 'bg-gray-100 cursor-not-allowed text-gray-500' : '' }}" 
                        {{ $data_rapida !== 'outro' ? 'readonly' : '' }}>
                    @error('appointment_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Check-in <span class="text-red-500">*</span></label>
                    <input type="time" wire:model.live.debounce.500ms="check_in" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('check_in') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Check-out <span class="text-red-500">*</span></label>
                    <input type="time" wire:model.live.debounce.500ms="check_out" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('check_out') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Qtd de Sessões</label>
                    <input type="number" wire:model="session_number" readonly class="block w-full border-gray-300 bg-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 cursor-not-allowed text-gray-600">
                    <p class="text-xs text-gray-500 mt-1">Calculado automaticamente</p>
                    @error('session_number') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- ═══ Convênio e unidade do atendimento ═══
                 Ficam recolhidos porque em 99% dos lançamentos o padrão do paciente está
                 correto. A faixa mostra o que será gravado; o botão só aparece para quem
                 pode alterar dado de faturamento. --}}
            @if($patient_id && $agreement_id)
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                    <div class="flex flex-wrap items-center gap-x-6 gap-y-1 text-sm">
                        <span class="flex items-center gap-1.5 text-gray-600">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                            Convênio:
                            <strong class="text-gray-900">{{ $agreements->firstWhere('id', (int) $agreement_id)?->name ?? '—' }}</strong>
                        </span>
                        <span class="flex items-center gap-1.5 text-gray-600">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Unidade:
                            <strong class="text-gray-900">{{ $units->firstWhere('id', (int) $unit_id)?->city ?? $units->firstWhere('id', (int) $unit_id)?->name ?? '—' }}</strong>
                        </span>
                    </div>

                    @if($this->podeAlterarFaturamento())
                        <button type="button" wire:click="abrirFaturamentoModal"
                                class="text-sm font-semibold text-blue-600 hover:text-blue-800 whitespace-nowrap">
                            Mudar informações do atendimento
                        </button>
                    @endif
                </div>
                @error('agreement_id') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                @error('unit_id') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
            @endif

            <div class="flex items-center justify-start gap-3 pt-6 border-t border-gray-200">
                <button type="submit" class="px-5 py-2 bg-blue-600 border border-transparent rounded-lg text-sm font-semibold text-white hover:bg-blue-700 flex items-center gap-2">
                    <span wire:loading.remove wire:target="save">Registrar Consulta</span>
                    <span wire:loading wire:target="save">Registrando...</span>
                </button>
                <button type="button" wire:click="saveAndCreateAnother" class="px-5 py-2 bg-white border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                    <span wire:loading.remove wire:target="saveAndCreateAnother">Salvar e criar outro</span>
                    <span wire:loading wire:target="saveAndCreateAnother">Registrando...</span>
                </button>
                <a href="{{ route('terapias-realizadas.index') }}" wire:navigate class="px-5 py-2 bg-white border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Cancelar
                </a>
            </div>
        </form>

        {{-- ═══ Modal: convênio e unidade do atendimento ═══ --}}
        @if($showFaturamentoModal)
            <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
                <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 backdrop-blur-sm transition-opacity" wire:click="fecharFaturamentoModal"></div>
                    <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

                    <div class="inline-block w-full transform overflow-hidden rounded-xl bg-white text-left align-bottom shadow-2xl transition-all sm:my-8 sm:max-w-lg sm:align-middle">

                        <div class="border-b border-gray-200 px-6 py-4">
                            <h3 class="text-base font-bold text-gray-900">Informações do atendimento</h3>
                            <p class="mt-1 text-xs leading-relaxed text-gray-500">
                                Por padrão, o atendimento herda o convênio e a unidade do cadastro do paciente.
                                Altere apenas quando este atendimento tiver ocorrido em condição diferente
                            </p>
                        </div>

                        <div class="space-y-4 px-6 py-5">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Convênio</label>
                                <select wire:model.live="agreement_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    @foreach($agreements as $agreement)
                                        <option value="{{ $agreement->id }}">{{ $agreement->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Unidade</label>
                                <select wire:model.live="unit_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    @foreach($units as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->city ?? $unit->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            @if($session_number)
                                <div class="rounded-md bg-blue-50 px-3 py-2 text-xs text-blue-800">
                                    Sessões recalculadas com esta regra: <strong>{{ $session_number }}</strong>
                                </div>
                            @endif
                        </div>

                        <div class="flex items-center justify-between gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                            <button type="button" wire:click="restaurarPadraoPaciente"
                                    class="text-sm font-semibold text-gray-500 hover:text-gray-900">
                                Restaurar padrão do paciente
                            </button>
                            <button type="button" wire:click="fecharFaturamentoModal"
                                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                                Concluir
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>