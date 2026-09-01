<div>
    <!-- Notificação Toast (mesmo padrão do restante da ficha do paciente) -->
    @if (session()->has('message'))
        <div x-data="{ show: true }"
             x-show="show"
             x-init="setTimeout(() => show = false, 3000)"
             x-transition
             class="fixed bottom-4 right-4 z-50 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center gap-2 shadow-lg">
            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <span class="font-medium text-sm">{{ session('message') }}</span>
        </div>
    @endif

    @php
        $coresPasta = [
            'docs_pessoais' => ['bg' => 'bg-amber-50', 'border' => 'border-amber-200', 'icon' => 'text-amber-500', 'badge' => 'bg-amber-100 text-amber-700'],
            'laudos'        => ['bg' => 'bg-blue-50', 'border' => 'border-blue-200', 'icon' => 'text-blue-500', 'badge' => 'bg-blue-100 text-blue-700'],
            'anamnese'      => ['bg' => 'bg-purple-50', 'border' => 'border-purple-200', 'icon' => 'text-purple-500', 'badge' => 'bg-purple-100 text-purple-700'],
            'relatorios'    => ['bg' => 'bg-emerald-50', 'border' => 'border-emerald-200', 'icon' => 'text-emerald-500', 'badge' => 'bg-emerald-100 text-emerald-700'],
            'outros'        => ['bg' => 'bg-gray-50', 'border' => 'border-gray-200', 'icon' => 'text-gray-400', 'badge' => 'bg-gray-200 text-gray-700'],
        ];
    @endphp

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden p-6">
        <div class="flex justify-between items-start mb-6 border-b pb-4">
            <div>
                @if($pastaAtual)
                    <button wire:click="voltarParaPastas" class="flex items-center gap-1 text-xs font-semibold text-gray-500 hover:text-blue-600 mb-1.5 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                        Todas as pastas
                    </button>
                    <h3 class="text-lg font-bold text-gray-900 uppercase">{{ $pastas[$pastaAtual]['label'] }}</h3>
                @else
                    <h3 class="text-lg font-bold text-gray-900 uppercase">Laudos e Documentos</h3>
                @endif
            </div>
            <button wire:click="abrirModal" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-semibold hover:bg-blue-700 transition shadow-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Adicionar Documento
            </button>
        </div>

        @if(! $pastaAtual)
            {{-- Grade de pastas --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                @foreach($pastas as $chave => $pasta)
                    @php $cor = $coresPasta[$chave]; @endphp
                    <button wire:click="abrirPasta('{{ $chave }}')"
                            class="group flex flex-col items-start gap-3 rounded-xl border {{ $cor['border'] }} {{ $cor['bg'] }} p-5 text-left transition-transform hover:-translate-y-0.5 hover:shadow-md">
                        <svg class="w-9 h-9 {{ $cor['icon'] }}" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 6a2 2 0 012-2h4l2 2h6a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                        </svg>
                        <div>
                            <p class="font-bold text-gray-900">{{ $pasta['label'] }}</p>
                            <span class="mt-1 inline-flex items-center rounded-full {{ $cor['badge'] }} px-2 py-0.5 text-xs font-semibold">
                                {{ $pasta['total'] }} {{ $pasta['total'] === 1 ? 'documento' : 'documentos' }}
                            </span>
                        </div>
                    </button>
                @endforeach
            </div>
        @else
            {{-- Conteúdo da pasta --}}
            <div class="overflow-x-auto border border-gray-200 rounded-lg">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase tracking-wider text-gray-600 font-semibold">
                            <th class="py-3 px-4">Nome</th>
                            <th class="py-3 px-4">Categoria</th>
                            <th class="py-3 px-4">Tamanho</th>
                            <th class="py-3 px-4">Enviado por</th>
                            <th class="py-3 px-4">Data</th>
                            <th class="py-3 px-4 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm text-gray-800">
                        @forelse ($documentos as $documento)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="py-3 px-4 font-medium">{{ $documento->nome_original }}</td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center rounded bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700">
                                        {{ $documento->categoriaLabel() }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-gray-500">{{ $documento->tamanhoFormatado() }}</td>
                                <td class="py-3 px-4 text-gray-500">{{ $documento->uploadedBy->name ?? '—' }}</td>
                                <td class="py-3 px-4 text-gray-500">{{ $documento->created_at->format('d/m/Y H:i') }}</td>
                                <td class="py-3 px-4 text-right space-x-3">
                                    <a href="{{ route('documentos.visualizar', $documento) }}" target="_blank" class="text-gray-600 hover:text-gray-900 font-medium text-sm transition-colors">
                                        Visualizar
                                    </a>
                                    <a href="{{ route('documentos.baixar', $documento) }}" target="_blank" class="text-blue-600 hover:text-blue-800 font-medium text-sm transition-colors">
                                        Baixar
                                    </a>
                                    <button wire:click="excluir({{ $documento->id }})" wire:confirm="Tem certeza que deseja excluir este documento?" class="text-red-600 hover:text-red-800 font-medium text-sm transition-colors">
                                        Excluir
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-gray-500">
                                    Nenhum documento nesta pasta ainda.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Modal de Upload -->
    <div x-data="{ open: $wire.entangle('isModalOpen') }"
         x-show="open"
         style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title"
         role="dialog"
         aria-modal="true">

        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">

            <div x-show="open"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-600 bg-opacity-75 transition-opacity backdrop-blur-sm"
                 wire:click="fecharModal"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="open"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">

                <form wire:submit.prevent="salvar">
                    <div class="bg-white px-6 pt-6 pb-6">
                        <div class="flex justify-between items-center mb-4 border-b pb-3">
                            <h3 class="text-lg leading-6 font-bold text-gray-900" id="modal-title">
                                Adicionar Documento
                            </h3>
                            <button type="button" wire:click="fecharModal" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                                <span class="sr-only">Fechar</span>
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Categoria <span class="text-red-500">*</span></label>
                                <select wire:model="categoria" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="">Selecione a categoria</option>
                                    @foreach($categoriaOptions as $valor => $rotulo)
                                        <option value="{{ $valor }}">{{ $rotulo }}</option>
                                    @endforeach
                                </select>
                                @error('categoria') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Arquivo <span class="text-red-500">*</span></label>
                                <input type="file" wire:model="arquivo" accept=".pdf,.jpg,.jpeg,.png"
                                       class="mt-1 block w-full text-sm text-gray-700 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                <p class="mt-1 text-xs text-gray-400">PDF, JPG ou PNG — até 10MB.</p>
                                <div wire:loading wire:target="arquivo" class="mt-1 text-xs text-blue-600">Enviando...</div>
                                @error('arquivo') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end gap-3 rounded-b-xl">
                        <button type="button" wire:click="fecharModal" class="px-4 py-2 border border-gray-300 text-gray-700 bg-white rounded-md hover:bg-gray-100 text-sm font-semibold transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Cancelar
                        </button>

                        <button type="submit" wire:loading.attr="disabled" wire:target="salvar" class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-md hover:bg-blue-700 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-60">
                            Salvar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
