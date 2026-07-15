<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
                    Usuários
                </h2>
                <div class="text-sm text-gray-500 mt-1 flex items-center gap-2">
                    <a href="#" class="hover:text-blue-600">Usuários</a>
                    <span>></span>
                    <span>Listar</span>
                </div>
            </div>
            
            <a href="{{ route('usuarios.create') }}" wire:navigate class="inline-flex items-center px-4 py-2 bg-blue-500 border border-transparent rounded-lg font-semibold text-sm text-white hover:bg-blue-600 transition ease-in-out duration-150">
                Criar Usuário
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-200">
                
                <div class="p-4 border-b border-gray-200 flex flex-col sm:flex-row justify-between gap-4 bg-gray-50">
                    
                    <div class="w-full sm:w-48">
                        <select wire:model.live="filtroStatus" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm text-gray-700">
                            <option value="ativos">Usuários Ativos</option>
                            <option value="excluidos">Lixeira (Excluídos)</option>
                        </select>
                    </div>

                    <div class="relative w-full md:w-1/3">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Pesquisar..." class="pl-10 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-white border-b border-gray-200">
                            <tr>
                                <th class="py-3 px-6 font-semibold text-gray-700">Nome</th>
                                <th class="py-3 px-6 font-semibold text-gray-700">E-mail</th>
                                <th class="py-3 px-6 font-semibold text-gray-700">Unidade(s)</th>
                                <th class="py-3 px-6 font-semibold text-gray-700">Função</th>
                                <th class="py-3 px-6 font-semibold text-gray-700">Acesso Produção</th>
                                <th class="py-3 px-6 font-semibold text-gray-700 text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($usuarios as $usuario)
                                <tr class="hover:bg-gray-50 transition-colors {{ $usuario->trashed() ? 'bg-red-50/30' : '' }}">
                                    <td class="py-3 px-6 text-gray-900">
                                        <div class="flex items-center gap-2">
                                            {{ $usuario->name }}
                                            
                                            @if($usuario->trashed())
                                                <span class="px-2 py-0.5 bg-red-100 text-red-700 text-[10px] font-bold rounded-full uppercase tracking-wider">
                                                    Excluído
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <td class="py-3 px-6 text-gray-600">{{ $usuario->email }}</td>
                                    
                                    <td class="py-3 px-6">
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($usuario->units as $unit)
                                                <span class="px-2 py-0.5 bg-blue-50 text-blue-600 text-xs font-semibold rounded border border-blue-100">
                                                    {{ $unit->city ?? 'Unidade' }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>

                                    <td class="py-3 px-6">
                                        <div class="flex flex-wrap gap-1">
                                            @forelse($usuario->roles as $role)
                                                @php
                                                    $roleColors = [
                                                        'admin' => 'bg-red-50 text-red-700 border-red-100',
                                                        'manager' => 'bg-orange-50 text-orange-700 border-orange-100',
                                                        'administrative' => 'bg-gray-50 text-gray-700 border-gray-200',
                                                        'coordinator' => 'bg-blue-50 text-blue-700 border-blue-100',
                                                        'supervisor' => 'bg-green-50 text-green-700 border-green-100',
                                                        'profissional' => 'bg-purple-50 text-purple-700 border-purple-100',
                                                    ];
                                                    $roleNames = [
                                                        'admin' => 'Administrador',
                                                        'manager' => 'Gerência Geral',
                                                        'administrative' => 'Administrativo',
                                                        'coordinator' => 'Coordenação',
                                                        'supervisor' => 'Supervisão',
                                                        'profissional' => 'Profissional',
                                                    ];
                                                    
                                                    $color = $roleColors[$role->name] ?? 'bg-gray-50 text-gray-700 border-gray-200';
                                                    $name = $roleNames[$role->name] ?? ucfirst($role->name);
                                                @endphp
                                                <span class="px-2 py-0.5 text-xs font-semibold rounded border {{ $color }}">
                                                    {{ $name }}
                                                </span>
                                            @empty
                                                <span class="px-2 py-0.5 text-xs font-semibold rounded border bg-gray-50 text-gray-500 border-gray-200">
                                                    Sem Função
                                                </span>
                                            @endforelse
                                        </div>
                                    </td>

                                    <td class="py-3 px-6">
                                        <button wire:click="toggleProductionAccess({{ $usuario->id }})" 
                                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $usuario->can_access_production ? 'bg-blue-500' : 'bg-gray-200' }}">
                                            <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $usuario->can_access_production ? 'translate-x-5' : 'translate-x-0' }}"></span>
                                        </button>
                                    </td>

                                    <td class="py-3 px-6 text-right">
                                        @if($usuario->trashed())
                                            <button wire:click="restoreUser({{ $usuario->id }})" wire:confirm="Deseja restaurar este usuário?" class="inline-flex items-center text-green-600 hover:text-green-800 font-semibold mr-3 text-sm">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                                                Restaurar
                                            </button>
                                            
                                            <button wire:click="forceDeleteUser({{ $usuario->id }})" wire:confirm="ATENÇÃO: Esta ação é irreversível e excluirá o usuário definitivamente do banco de dados. Deseja continuar?" class="inline-flex items-center text-red-600 hover:text-red-800 font-semibold text-sm">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                Excluir Definitivo
                                            </button>
                                        @else
                                            <a href="{{ route('usuarios.edit', $usuario->id) }}" wire:navigate class="inline-flex items-center text-blue-600 hover:text-blue-800 font-semibold mr-3 text-sm">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                                Editar
                                            </a>
                                            
                                            <button wire:click="deleteUser({{ $usuario->id }})" wire:confirm="Mover este usuário para a lixeira?" class="inline-flex items-center text-red-600 hover:text-red-800 font-semibold text-sm">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                Excluir
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-gray-500">
                                        Nenhum usuário encontrado.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($usuarios->hasPages())
                    <div class="p-4 border-t border-gray-200 bg-gray-50">
                        {{ $usuarios->links() }}
                    </div>
                @endif

            </div>
        </div>
    </div>
</div>