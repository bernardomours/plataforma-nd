<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
                    Editar Usuário
                </h2>
                <div class="text-sm text-gray-500 mt-1 flex items-center gap-2">
                    <a href="{{ route('usuarios.index') }}" wire:navigate class="hover:text-blue-600">Usuários</a>
                    <span>></span>
                    <span>Editar</span>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-8 max-w-4xl mx-auto sm:px-6 lg:px-8">
        
        @if (session()->has('message'))
            <div class="mb-4 p-4 bg-green-50 text-green-700 rounded-lg border border-green-200">
                {{ session('message') }}
            </div>
        @endif

        <div class="bg-white shadow-sm sm:rounded-xl border border-gray-200">
            <div class="p-6 border-b border-gray-100 bg-gray-50/50 rounded-t-xl">
                <h3 class="text-lg font-bold text-gray-900">Editar {{ $name }}</h3>
                <p class="text-sm text-gray-500">Atualize os dados e acessos deste colaborador.</p>
            </div>

            <form wire:submit="update" class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Nome Completo <span class="text-red-500">*</span></label>
                        <input wire:model="name" type="text" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                        @error('name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">E-mail <span class="text-red-500">*</span></label>
                        <input wire:model="email" type="email" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                        @error('email') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Nova Senha</label>
                        <input wire:model="password" type="password" placeholder="Deixe em branco para manter a atual" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-gray-50">
                        @error('password') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Data de Nascimento</label>
                        <input wire:model="birth_date" type="date" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('birth_date') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <hr class="border-gray-100">

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-3">Funções / Cargos de Acesso <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                        @php
                            $roleNames = [
                                'admin' => 'Administrador',
                                'manager' => 'Gerência Geral',
                                'administrative' => 'Administrativo',
                                'coordinator' => 'Coordenação',
                                'supervisor' => 'Supervisão',
                                'profissional' => 'Profissional',
                            ];
                        @endphp
                        
                        @foreach($todasRoles as $role)
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model="selected_roles" value="{{ $role->name }}" class="w-4 h-4 text-purple-600 bg-white border-gray-300 rounded focus:ring-purple-500">
                                <span class="text-sm font-medium text-gray-700">{{ $roleNames[$role->name] ?? ucfirst($role->name) }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('selected_roles') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Acesso ao Financeiro (Produção)</label>
                        <label class="inline-flex items-center cursor-pointer">
                            <input wire:model="can_access_production" type="checkbox" class="sr-only peer">
                            <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500"></div>
                            <span class="ms-3 text-sm font-medium text-gray-700">Permitir Acesso</span>
                        </label>
                        <p class="text-xs text-gray-500 mt-2">Permite que este usuário acesse a rota /producao e veja faturamentos.</p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-3">Unidades Vinculadas <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                        @foreach($unidades as $unidade)
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model="selected_units" value="{{ $unidade->id }}" class="w-4 h-4 text-blue-600 bg-white border-gray-300 rounded focus:ring-blue-500">
                                <span class="text-sm font-medium text-gray-700">{{ $unidade->city }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('selected_units') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="flex items-center gap-3 pt-4 border-t border-gray-100">
                    <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white rounded-lg font-bold shadow-sm hover:bg-blue-700 transition-colors">
                        Salvar Alterações
                    </button>
                    <a href="{{ route('usuarios.index') }}" wire:navigate class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-lg font-bold hover:bg-gray-50 transition-colors">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>