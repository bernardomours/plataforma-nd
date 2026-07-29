<div class="max-w-4xl mx-auto py-8 sm:px-6 lg:px-8">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Novo Processo da Qualidade</h2>
            <p class="text-sm text-gray-500 mt-1">Cadastre um novo POP e suas etapas.</p>
        </div>
        <a href="{{ route('qualidade.index') }}" class="text-gray-600 hover:text-gray-900 font-medium">
            &larr; Voltar
        </a>
    </div>

    <form wire:submit.prevent="save" class="bg-white shadow-sm border border-gray-200 rounded-xl p-6 space-y-6">
        
        <!-- Bloco 1: Informações Principais -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700">Setor</label>
                <input type="text" wire:model="sector" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="Ex: Faturamento">
                @error('sector') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Código do Procedimento (POP)</label>
                <input type="text" wire:model="procedure_code" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="Ex: POP-FAT01-001">
                @error('procedure_code') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Nome do Processo</label>
                <input type="text" wire:model="process_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="Ex: Solicitação">
                @error('process_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Prazo Final</label>
                <input type="date" wire:model="due_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                @error('due_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
        </div>

        <hr class="border-gray-200">

        <!-- Bloco 2: Responsáveis (Dropdown Pesquisável com Alpine.js) -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Responsáveis</label>
            
            <!-- 1. O @click.away foi movido para cá, englobando tudo -->
            <div x-data="{
                open: false,
                search: '',
                selected: @entangle('selectedUsers'),
                users: {{ $allUsers->map(function($u) { return ['id' => (string)$u->id, 'name' => $u->name]; })->toJson() }},
                
                get filteredUsers() {
                    if (this.search === '') return this.users;
                    return this.users.filter(user => user.name.toLowerCase().includes(this.search.toLowerCase()));
                },
                
                toggleUser(id) {
                    let strId = String(id);
                    let index = this.selected.findIndex(i => String(i) === strId);
                    if (index > -1) {
                        this.selected.splice(index, 1);
                    } else {
                        this.selected.push(strId);
                    }
                    this.search = ''; // Limpa a busca para facilitar selecionar o próximo
                    $refs.searchInput.focus(); // Mantém o foco no campo
                }
            }" class="relative" @click.away="open = false">
                
                <!-- Input falso que parece um Select -->
                <div class="relative">
                    <input
                        x-ref="searchInput"
                        type="text"
                        x-model="search"
                        @focus="open = true"
                        @click="open = true"
                        @keydown.escape="open = false"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm pr-10 cursor-pointer"
                        placeholder="Selecione ou busque os responsáveis..."
                    >
                    <!-- Ícone de setinhas para cima/baixo -->
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.76 9.2a.75.75 0 011.06.04l2.7 2.908 2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 01.04-1.06z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>

                <!-- Lista Suspensa (Dropdown) -->
                <div x-show="open" x-transition style="display: none;" class="absolute z-10 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto sm:text-sm">
                    <template x-for="user in filteredUsers" :key="user.id">
                        <div
                            @click="toggleUser(user.id)"
                            class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-blue-50 transition-colors"
                        >
                            <span x-text="user.name" class="block truncate" :class="(selected.includes(user.id) || selected.includes(String(user.id))) ? 'font-semibold text-blue-700' : 'font-normal text-gray-900'"></span>
                            
                            <!-- Ícone de Checkmark para quem já foi selecionado -->
                            <span x-show="selected.includes(user.id) || selected.includes(String(user.id))" class="absolute inset-y-0 right-0 flex items-center pr-4 text-blue-600">
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </div>
                    </template>
                    
                    <!-- Mensagem de fallback -->
                    <div x-show="filteredUsers.length === 0" class="cursor-default select-none relative py-2 pl-3 pr-9 text-gray-500">
                        Nenhum colaborador encontrado.
                    </div>
                </div>

                <!-- Tags / Pills (Mostra os usuários que foram selecionados) -->
                <div class="flex flex-wrap gap-2 mt-3" x-show="selected.length > 0" style="display: none;">
                    <template x-for="id in selected" :key="id">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-sm font-medium bg-blue-100 text-blue-800 border border-blue-200">
                            <span x-text="users.find(u => String(u.id) === String(id))?.name"></span>
                            <button type="button" @click="toggleUser(id)" class="ml-1.5 inline-flex text-blue-500 hover:text-blue-700 focus:outline-none" title="Remover">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                            </button>
                        </span>
                    </template>
                </div>
            </div>
            
            @error('selectedUsers') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
        </div>

        <hr class="border-gray-200">

        <!-- Bloco 3: O Checklist Dinâmico -->
        <div>
            <div class="flex items-center justify-between mb-4">
                <label class="block text-sm font-medium text-gray-700">Etapas do Checklist</label>
                <button type="button" wire:click="addChecklist" class="text-sm text-blue-600 font-medium hover:text-blue-800 flex items-center">
                    + Adicionar Etapa
                </button>
            </div>

            <div class="space-y-3">
                @foreach($checklists as $index => $checklist)
                    <div class="flex items-center space-x-3">
                        <span class="text-sm font-bold text-gray-400">{{ $index + 1 }}.</span>
                        <input type="text" wire:model="checklists.{{ $index }}.description" class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="Descreva a tarefa a ser cumprida...">
                        
                        @if(count($checklists) > 1)
                            <button type="button" wire:click="removeChecklist({{ $index }})" class="text-red-500 hover:text-red-700 focus:outline-none" title="Remover Etapa">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        @endif
                    </div>
                    @error('checklists.'.$index.'.description') <span class="text-red-500 text-xs block">{{ $message }}</span> @enderror
                @endforeach
            </div>
        </div>

        <!-- Botão Salvar -->
        <div class="pt-4 flex justify-end">
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg shadow-sm transition">
                Salvar Processo
            </button>
        </div>
    </form>
</div>