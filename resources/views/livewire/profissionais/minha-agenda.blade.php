    <div class="w-full">

        @if (session()->has('message'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" x-transition
                 class="mb-3 flex items-center gap-2 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-xs font-medium text-green-700">
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                {{ session('message') }}
            </div>
        @endif
        @if (session()->has('error'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition
                 class="mb-3 flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-medium text-red-700">
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                {{ session('error') }}
            </div>
        @endif

        @if($agendamentos->isEmpty())
            <div class="flex flex-col items-center justify-center py-8 text-center">
                <div class="bg-gray-100 p-3 rounded-full mb-3">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h4 class="text-gray-700 font-medium">Nenhum atendimento para hoje</h4>
                <p class="text-xs text-gray-500 mt-1">Aproveite o tempo livre na sua {{ $diaSemana }}.</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($agendamentos as $horario)
                    @php
                        $horaInicio = \Carbon\Carbon::parse($horario->start_time)->format('H:i');
                        $horaFim = \Carbon\Carbon::parse($horario->end_time)->format('H:i');
                        $isPassado = \Carbon\Carbon::parse($horario->end_time)->isPast();
                        $item = $statusPorSchedule->get($horario->id);
                        $status = $item->status ?? 'pendente';
                    @endphp

                    <div class="flex items-start gap-4 p-3 rounded-lg border {{ $isPassado ? 'bg-gray-50 border-gray-100 opacity-60' : 'bg-white border-blue-100 shadow-sm hover:shadow-md transition-shadow' }}">

                        <div class="flex flex-col items-center justify-center min-w-[60px]">
                            <span class="text-sm font-bold {{ $isPassado ? 'text-gray-500' : 'text-blue-600' }}">{{ $horaInicio }}</span>
                            <span class="text-[10px] text-gray-400 font-medium">{{ $horaFim }}</span>
                        </div>

                        <div class="w-1 rounded-full {{ $isPassado ? 'bg-gray-300' : 'bg-blue-400' }} self-stretch"></div>

                        <div class="flex-1 min-w-0">
                            <h4 class="text-sm font-bold text-gray-900 truncate" title="{{ $horario->patient?->name ?? 'Paciente Removido' }}">
                                {{ $horario->patient?->name ?? 'Paciente Indefinido/Removido' }}
                            </h4>

                            <div class="flex flex-wrap items-center gap-2 mt-1.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                    {{ $horario->therapy?->name ?? 'Terapia' }}
                                </span>

                                @if($horario->serviceType)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-gray-100 text-gray-700">
                                        {{ $horario->serviceType?->name }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="shrink-0">
                            @if($status === 'realizado')
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-[10px] font-bold bg-green-50 text-green-700 border border-green-100" title="Já lançado em Terapias Realizadas">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                    Atendido
                                </span>
                            @elseif($status === 'falta')
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-[10px] font-bold bg-red-50 text-red-700 border border-red-100" title="{{ $item->falta?->observacao }}">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    Falta · {{ $item->falta?->motivoLabel() }}
                                </span>
                            @else
                                <button type="button" wire:click="abrirModalFalta({{ $horario->id }})"
                                        class="inline-flex items-center gap-1 px-2 py-1 rounded text-[10px] font-bold text-red-600 border border-red-200 hover:bg-red-50 transition-colors">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    Registrar Falta
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Modal: Registrar Falta --}}
        <div x-data="{ open: @entangle('isModalFaltaOpen') }" x-show="open" x-cloak
             @keydown.escape.window="open && $wire.fecharModalFalta()"
             class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
            <div x-show="open" x-transition.opacity @click="$wire.fecharModalFalta()" class="absolute inset-0 bg-gray-900/50"></div>

            <div x-show="open" x-transition:enter="ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                 class="relative w-full max-w-md overflow-hidden rounded-xl bg-white text-left shadow-xl border border-gray-200">
                <form wire:submit.prevent="salvarFalta">
                    <div class="p-6">
                        <div class="flex items-start gap-3 mb-4">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-50">
                                <svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900">Registrar falta</h3>
                                <p class="mt-0.5 text-sm text-gray-500">Fica registrado o motivo.</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Motivo</label>
                                <select wire:model="motivo" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Selecione...</option>
                                    @foreach($motivoOptions as $valor => $rotulo)
                                        <option value="{{ $valor }}">{{ $rotulo }}</option>
                                    @endforeach
                                </select>
                                @error('motivo') <span class="mt-1 block text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Observação <span class="font-normal text-gray-400">(opcional)</span></label>
                                <textarea wire:model="observacao" rows="2" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Algum detalhe a mais, se precisar"></textarea>
                                @error('observacao') <span class="mt-1 block text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-2 border-t border-gray-200 bg-gray-50 p-4 sm:flex-row sm:justify-end">
                        <button type="button" wire:click="fecharModalFalta" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition-colors hover:bg-gray-100">
                            Cancelar
                        </button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="salvarFalta"
                                class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-red-700 disabled:opacity-60">
                            <span wire:loading.remove wire:target="salvarFalta">Registrar Falta</span>
                            <span wire:loading wire:target="salvarFalta">Salvando...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>