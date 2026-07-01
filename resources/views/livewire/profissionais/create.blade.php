<div>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            
            <a href="{{ route('profissionais.index') }}" wire:navigate class="p-2 -ml-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500" title="Voltar para a lista de profissionais">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            
            <div class="flex flex-col">
                <nav class="flex text-xs text-gray-500 mb-1" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1">
                        <li>
                            <a href="{{ route('profissionais.index') }}" wire:navigate class="hover:text-blue-600 transition-colors">Profissionais</a>
                        </li>
                        <li><span class="mx-1 text-gray-400">/</span></li>
                        <li aria-current="page" class="text-gray-700 font-medium">Cadastrar Profissional</li>
                    </ol>
                </nav>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Cadastrar Profissional
                </h2>
            </div>

        </div>
    </x-slot>

    <style>
        .ts-control {
            padding: 0.5rem 0.75rem !important;
            border-radius: 0.375rem !important;
            border-color: #d1d5db !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
            min-height: 42px !important;
            cursor: pointer !important;
        }
        .ts-control input { cursor: pointer !important; }
        .ts-control.focus input { cursor: text !important; }
        .ts-control .item {
            background-color: #eff6ff !important;
            color: #1d4ed8 !important;
            border: 1px solid #bfdbfe !important;
            border-radius: 0.375rem !important;
            padding: 2px 8px !important;
            font-weight: 500 !important;
            font-size: 0.875rem !important;
        }
        .ts-control .item .remove {
            border-left: 1px solid #bfdbfe !important;
            color: #1d4ed8 !important;
        }
        .ts-dropdown {
            border-radius: 0.375rem !important;
            border-color: #d1d5db !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
        }
    </style>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <form wire:submit.prevent="save" class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 p-6 space-y-6">
            
            @if (session()->has('message'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" 
                    class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg relative flex items-center gap-2" role="alert">
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span class="block sm:inline font-medium text-sm">{{ session('message') }}</span>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nome <span class="text-red-500">*</span></label>
                    <input wire:model="name" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">CPF <span class="text-red-500">*</span></label>
                    <input 
                        wire:model="cpf" 
                        type="text" 
                        maxlength="14"
                        x-data="{
                            formatCpf() {
                                let value = $el.value.replace(/\D/g, ''); 
                                value = value.replace(/(\d{3})(\d)/, '$1.$2'); 
                                value = value.replace(/(\d{3})(\d)/, '$1.$2'); 
                                value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2'); 
                                $el.value = value;
                            }
                        }"
                        x-on:input="formatCpf()"
                        placeholder="000.000.000-00" 
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                    >
                    <p class="text-xs text-gray-500 mt-1">Digite apenas os números, o sistema formata automaticamente.</p>
                    @error('cpf') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Telefone <span class="text-red-500">*</span></label>
                    <input wire:model="phone" type="text" placeholder="84999999999" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('phone') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Data de Nascimento <span class="text-red-500">*</span></label>
                    <input wire:model="birth_date" type="date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('birth_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Número de Registro</label>
                    <input wire:model="register_number" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('register_number') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input wire:model="email" type="email" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <hr class="border-gray-200">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Função / Cargo <span class="text-red-500">*</span></label>
                    <div wire:ignore>
                        <select x-data="{
                            init() {
                                let tom = new TomSelect(this.$el, {
                                    placeholder: 'Selecione o Cargo...',
                                    onChange: (value) => { $wire.set('role', value) }
                                });
                                window.addEventListener('clear-tom-selects', () => { tom.clear(true); });
                            }
                        }" class="w-full">
                            <option value="">Selecione o Cargo</option>
                            @foreach($roles as $roleOption)
                                <option value="{{ $roleOption->value }}">{{ $roleOption->getLabel() }}</option>
                            @endforeach
                        </select>
                    </div>
                    @error('role') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Unidades <span class="text-red-500">*</span></label>
                    <div wire:ignore>
                        <select x-data="{
                            init() {
                                let tom = new TomSelect(this.$el, {
                                    plugins: ['remove_button'],
                                    placeholder: 'Selecione as unidades...',
                                    onChange: (value) => { $wire.set('selectedUnits', value) }
                                });
                                window.addEventListener('clear-tom-selects', () => { tom.clear(true); });
                            }
                        }" multiple class="w-full">
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->city ?? $unit->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @error('selectedUnits') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Especialidade(s)</label>
                    <div wire:ignore>
                        <select x-data="{
                            init() {
                                let tom = new TomSelect(this.$el, {
                                    plugins: ['remove_button'],
                                    placeholder: 'Selecione as especialidades...',
                                    onChange: (value) => { $wire.set('selectedTherapies', value) }
                                });
                                window.addEventListener('clear-tom-selects', () => { tom.clear(true); });
                            }
                        }" multiple class="w-full">
                            @foreach($therapies as $therapy)
                                <option value="{{ $therapy->id }}">{{ $therapy->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @error('selectedTherapies') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="flex items-center justify-start gap-3 pt-6 mt-6 border-t border-gray-200">
                <button type="submit" class="px-5 py-2 bg-blue-600 border border-transparent rounded-lg text-sm font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2">
                    <span wire:loading.remove wire:target="save">Criar</span>
                    <span wire:loading wire:target="save">Salvando...</span>
                </button>
                <button type="button" wire:click="saveAndCreateAnother" class="px-5 py-2 bg-white border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none transition-colors flex items-center gap-2">
                    <span wire:loading.remove wire:target="saveAndCreateAnother">Salvar e criar outro</span>
                    <span wire:loading wire:target="saveAndCreateAnother">Salvando...</span>
                </button>
                <a href="{{ route('profissionais.index') }}" wire:navigate class="px-5 py-2 bg-white border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none transition-colors">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>