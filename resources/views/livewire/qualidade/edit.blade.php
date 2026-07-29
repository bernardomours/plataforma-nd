<div class="max-w-4xl mx-auto py-8 sm:px-6 lg:px-8">
    
    <!-- Cabeçalho -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Editar Processo de Qualidade</h2>
                <p class="text-sm text-gray-500 mt-1">Atualize as informações, prazos, responsáveis e etapas.</p>
            </div>
            <a href="{{ route('qualidade.index') }}" class="text-gray-500 hover:text-gray-700 font-medium text-sm flex items-center transition-colors">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Voltar
            </a>
        </div>
    </div>

    <!-- Formulário -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <form wire:submit="update" class="p-6 space-y-6">
            
            <!-- Grid Principal -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <!-- Setor -->
                <div>
                    <label for="sector" class="block text-sm font-semibold text-gray-700 mb-1">Setor</label>
                    <input type="text" wire:model="sector" id="sector" placeholder="Ex: Faturamento e Operadoras" 
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-colors {{ $errors->has('sector') ? 'border-red-500' : '' }}">
                    @error('sector') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                </div>

                <!-- Código do Processo (POP) -->
                <div>
                    <label for="procedure_code" class="block text-sm font-semibold text-gray-700 mb-1">Código do Processo (POP)</label>
                    <input type="text" wire:model="procedure_code" id="procedure_code" placeholder="Ex: POP-FAT01-001" 
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-colors {{ $errors->has('procedure_code') ? 'border-red-500' : '' }}">
                    @error('procedure_code') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                </div>

                <!-- Nome do Processo -->
                <div class="md:col-span-2">
                    <label for="process_name" class="block text-sm font-semibold text-gray-700 mb-1">Nome da Tarefa / Descrição</label>
                    <input type="text" wire:model="process_name" id="process_name" placeholder="Ex: Faturamento, Demanda de solicitação..." 
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-colors {{ $errors->has('process_name') ? 'border-red-500' : '' }}">
                    @error('process_name') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                </div>

                <!-- Prazo Final -->
                <div>
                    <label for="due_date" class="block text-sm font-semibold text-gray-700 mb-1">Prazo Final</label>
                    <input type="date" wire:model="due_date" id="due_date" 
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-colors {{ $errors->has('due_date') ? 'border-red-500' : '' }}">
                    @error('due_date') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                </div>

                <!-- Seleção de Usuários (Alpine.js) -->
                <div class="md:col-span-2" 
                     x-data="{
                        open: false,
                        search: '',
                        selected: @entangle('selectedUsers'),
                        users: {{ $allUsers->map(fn($u) => ['id' => $u->id, 'name' => $u->name])->toJson() }},
                        get filteredUsers() {
                            return this.users.filter(user => user.name.toLowerCase().includes(this.search.toLowerCase()));
                        },
                        toggleUser(id) {
                            if (this.selected.includes(id)) {
                                this.selected = this.selected.filter(i => i !== id);
                            } else {
                                this.selected.push(id);
                            }
                        },
                        removeUser(id) {
                            this.selected = this.selected.filter(i => i !== id);
                        },
                        getUserName(id) {
                            const user = this.users.find(u => u.id === id);
                            return user ? user.name : '';
                        }
                     }">
                     
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Responsáveis</label>
                    
                    <div class="relative">
                        <!-- Input Falso que aciona o Dropdown e mostra as Tags -->
                        <div @click="open = !open" @click.away="open = false" 
                             class="min-h-[42px] w-full bg-white border border-gray-300 rounded-lg shadow-sm px-3 py-1.5 flex flex-wrap gap-2 items-center cursor-text focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500 transition-colors">
                            
                            <!-- Tags dos usuários selecionados -->
                            <template x-for="userId in selected" :key="userId">
                                <span class="inline-flex items-center bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-1 rounded-md">
                                    <span x-text="getUserName(userId)"></span>
                                    <button type="button" @click.stop="removeUser(userId)" class="ml-1.5 text-blue-600 hover:text-blue-900 focus:outline-none">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </span>
                            </template>

                            <!-- Placeholder se vazio -->
                            <span x-show="selected.length === 0" class="text-gray-400 text-sm py-1">Selecione um ou mais responsáveis...</span>
                            
                            <!-- Ícone de seta -->
                            <div class="ml-auto flex items-center pr-1 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </div>
                        </div>

                        <!-- Dropdown de Busca e Lista -->
                        <div x-show="open" 
                             x-transition:enter="transition ease-out duration-100" 
                             x-transition:enter-start="transform opacity-0 scale-95" 
                             x-transition:enter-end="transform opacity-100 scale-100" 
                             x-transition:leave="transition ease-in duration-75" 
                             x-transition:leave-start="transform opacity-100 scale-100" 
                             x-transition:leave-end="transform opacity-0 scale-95" 
                             class="absolute z-50 mt-1 w-full bg-white rounded-md shadow-lg border border-gray-200" style="display: none;">
                            
                            <!-- Campo de Busca -->
                            <div class="p-2 border-b border-gray-100">
                                <input type="text" x-model="search" placeholder="Buscar colaborador..." @click.stop
                                       class="w-full text-sm border-gray-300 rounded-md focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <!-- Lista de Usuários -->
                            <ul class="max-h-48 overflow-y-auto py-1">
                                <template x-for="user in filteredUsers" :key="user.id">
                                    <li @click="toggleUser(user.id)" 
                                        class="px-4 py-2 text-sm cursor-pointer flex items-center hover:bg-gray-50 transition-colors"
                                        :class="{'bg-blue-50 text-blue-900 font-semibold': selected.includes(user.id), 'text-gray-700': !selected.includes(user.id)}">
                                        
                                        <!-- Checkbox visual -->
                                        <div class="mr-3 flex items-center justify-center w-4 h-4 rounded border"
                                             :class="selected.includes(user.id) ? 'bg-blue-600 border-blue-600' : 'border-gray-300'">
                                            <svg x-show="selected.includes(user.id)" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                        </div>
                                        
                                        <span x-text="user.name"></span>
                                    </li>
                                </template>
                                <!-- Mensagem caso não encontre -->
                                <li x-show="filteredUsers.length === 0" class="px-4 py-3 text-sm text-gray-500 text-center">
                                    Nenhum colaborador encontrado.
                                </li>
                            </ul>
                        </div>
                    </div>
                    @error('selectedUsers') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- ================= ETAPAS / CHECKLIST ================= -->
            <div class="mt-8 pt-6 border-t border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <label class="block text-base font-semibold text-gray-800">Etapas do Processo (Checklist)</label>
                        <p class="text-xs text-gray-500">Crie, edite ou remova os passos em ordem cronológica.</p>
                    </div>
                    <button type="button" wire:click="addChecklist" class="text-sm font-semibold text-blue-600 hover:text-blue-800 flex items-center transition-colors bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-lg">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                        Adicionar Passo
                    </button>
                </div>

                <div class="space-y-3">
                    @foreach($checklists as $index => $step)
                        <div>
                            <div class="flex items-center space-x-3">
                                <!-- Número do passo -->
                                <div class="flex-shrink-0 w-8 h-8 bg-indigo-50 text-indigo-600 rounded-full flex items-center justify-center font-bold text-sm shadow-sm border border-indigo-100">
                                    {{ $index + 1 }}
                                </div>
                                
                                <!-- Input da descrição -->
                                <input type="text" wire:model="checklists.{{ $index }}.description" placeholder="Descreva a etapa (ex: Mandar pra Unimed)"
                                       class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 {{ $errors->has('checklists.'.$index.'.description') ? 'border-red-500' : '' }}">
                                
                                <!-- Botão Remover (Lixeira) -->
                                <button type="button" wire:click="removeChecklist({{ $index }})" class="p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors focus:outline-none" title="Remover Etapa">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                            @error('checklists.'.$index.'.description') <span class="text-xs text-red-500 mt-1 ml-11 block">{{ $message }}</span> @enderror
                        </div>
                    @endforeach
                    
                    @if(count($checklists) === 0)
                        <div class="text-center py-6 bg-gray-50 rounded-lg border border-dashed border-gray-300">
                            <p class="text-sm text-gray-500">Nenhuma etapa cadastrada. Clique em "Adicionar Passo" para começar a montar o POP.</p>
                        </div>
                    @endif
                </div>
            </div>
            <!-- ================= FIM ETAPAS ================= -->

            <!-- Botões de Ação -->
            <div class="pt-6 mt-6 border-t border-gray-100 flex items-center justify-end space-x-4">
                <a href="{{ route('qualidade.index') }}" class="px-4 py-2 text-sm font-semibold text-gray-600 hover:text-gray-900 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Cancelar
                </a>
                
                <button type="submit" wire:loading.attr="disabled" class="px-6 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 flex items-center disabled:opacity-70">
                    <span wire:loading.remove wire:target="update">Salvar Alterações</span>
                    <span wire:loading wire:target="update" class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Salvando...
                    </span>
                </button>
            </div>
            
        </form>
    </div>
</div>