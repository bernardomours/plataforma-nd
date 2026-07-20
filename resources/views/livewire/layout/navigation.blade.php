<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div x-data="{ open: false }">
    <!-- MENU DESKTOP -->
    <aside class="hidden md:flex flex-col fixed inset-y-0 left-0 w-64 bg-white border-r border-gray-200 z-50">
        
        <div class="h-20 flex items-center justify-center border-b border-gray-200 px-4">
            <a href="{{ route('dashboard') }}" wire:navigate>
                <img src="{{ asset('images/icon-nd.png') }}" class="w-44 h-24 max-h-24 object-contain transition-transform hover:scale-105" alt="Plataforma ND">
            </a>
        </div>

        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-2">
            <a href="{{ route('dashboard') }}" wire:navigate 
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('dashboard') ? 'bg-gray-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
                    
                    <svg class="w-5 h-5 {{ request()->routeIs('dashboard') ? 'text-blue-600' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    
                    <span class="font-medium text-sm">Início</span>
            </a>

            <a href="{{ route('servicos.index') }}" wire:navigate class="{{ request()->routeIs('servicos.*') ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                <svg class="{{ request()->routeIs('servicos.*') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-500' }} mr-3 flex-shrink-0 h-5 w-5 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                Serviços
            </a>

            <!-- ADICIONADO A ROLE PROFISSIONAL PARA ABRIR O MENU -->
            @hasanyrole('admin|manager|administrative|profissional')
            <div x-data="{ isOpen: true }" class="pt-4 pb-1">
                <button @click="isOpen = !isOpen" class="w-full flex items-center justify-between px-3 pb-2 text-xs font-semibold text-gray-500 hover:text-gray-700 transition-colors focus:outline-none">
                    <span>Frequência</span>
                    <svg :class="{ 'rotate-180': !isOpen }" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                </button>

                <div x-show="isOpen" x-transition class="space-y-1">
                    
                    @hasanyrole('admin|manager')
                        <a href="{{ route('relatorios.geral') }}" wire:navigate 
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('relatorios.*') ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
                            <svg class="w-5 h-5 {{ request()->routeIs('relatorios.*') ? 'text-blue-600' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            <span class="font-medium text-sm">Relatórios Gerais</span>
                        </a>
                    @endhasanyrole

                    <!-- VISÍVEL PARA PROFISSIONAIS -->
                    <a href="{{ route('terapias-realizadas.index') }}" wire:navigate class="{{ request()->routeIs('terapias-realizadas.*') ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                        <svg class="{{ request()->routeIs('terapias-realizadas.*') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-500' }} mr-3 flex-shrink-0 h-5 w-5 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                        </svg>
                        Terapias Realizadas
                    </a>

                    <!-- ESCONDIDO DOS PROFISSIONAIS -->
                    @hasanyrole('admin|manager|administrative')
                        <a href="{{ route('ch-solicitada.index') }}" wire:navigate class="{{ request()->routeIs('ch-solicitada.*') ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                            <svg class="{{ request()->routeIs('ch-solicitada.*') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-500' }} mr-3 flex-shrink-0 h-5 w-5 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            CH - Solicitada
                        </a>

                        <a href="{{ route('avaliacoes-neuro.index') }}" wire:navigate class="{{ request()->routeIs('avaliacoes-neuro.*') ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                            <svg class="{{ request()->routeIs('avaliacoes-neuro.*') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-500' }} mr-3 flex-shrink-0 h-5 w-5 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            Avaliações Neuro
                        </a>
                    @endhasanyrole

                </div>
            </div>
            @endhasanyrole

            @hasanyrole('admin|manager|administrative|coordinator|supervisor')
            <div x-data="{ isOpen: true }" class="pt-4 pb-1">
                <button @click="isOpen = !isOpen" class="w-full flex items-center justify-between px-3 pb-2 text-xs font-semibold text-gray-500 hover:text-gray-700 transition-colors focus:outline-none">
                    <span>Coordenação / Supervisão</span>
                    <svg :class="{ 'rotate-180': !isOpen }" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                </button>

                <div x-show="isOpen" x-transition class="space-y-1">

                    <a href="{{ route('acompanhamentos.index') }}" wire:navigate class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('acompanhamentos*') ? 'bg-gray-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('acompanhamentos*') ? 'text-blue-600' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <span class="font-medium text-sm">Acompanhamentos</span>
                    </a>

                    <a href="{{ route('cronograma.index') }}" wire:navigate class="{{ request()->routeIs('cronograma.*') ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors mt-1">
                        <svg class="{{ request()->routeIs('cronograma.*') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-500' }} mr-3 flex-shrink-0 h-5 w-5 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                        </svg>
                        Cronograma de Terapias ABA/DENVER
                    </a>
                    
                    @hasanyrole('admin|manager|administrative')
                    <a href="{{ route('vinculos.index') }}" wire:navigate class="{{ request()->routeIs('vinculos.*') ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors mt-1">
                        <svg class="{{ request()->routeIs('vinculos.*') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-500' }} mr-3 flex-shrink-0 h-5 w-5 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        Vínculos de Pacientes
                    </a>
                    @endhasanyrole
                </div>
            </div>
            @endhasanyrole

            <div x-data="{ isOpen: true }" class="pt-4 pb-1">
                <button @click="isOpen = !isOpen" class="w-full flex items-center justify-between px-3 pb-2 text-xs font-semibold text-gray-500 hover:text-gray-700 transition-colors focus:outline-none">
                    <span>Ocupação</span>
                    <svg :class="{ 'rotate-180': !isOpen }" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                </button>

                <div x-show="isOpen" x-transition class="space-y-1">

                    <a href="{{ route('pacientes.index') }}" wire:navigate class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('pacientes*') ? 'bg-gray-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('pacientes*') ? 'text-blue-600' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <span class="font-medium text-sm">Pacientes</span>
                    </a>
                    
                    <a href="{{ route('agenda-profissionais.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50 transition-colors">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span class="font-medium text-sm">Agenda - Profissionais</span>
                    </a>
                    
                    @hasanyrole('admin|manager|administrative')
                    <a href="{{ route('profissionais.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('profissionais*') ? 'bg-gray-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('profissionais*') ? 'text-blue-600' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="font-medium text-sm">Profissionais</span>
                    </a>
                    @endhasanyrole
                </div>
            </div>

            @role('admin')
            <div x-data="{ isOpen: true }" class="pt-4 pb-1">
                <button @click="isOpen = !isOpen" class="w-full flex items-center justify-between px-3 pb-2 text-xs font-semibold text-gray-500 hover:text-gray-700 transition-colors focus:outline-none">
                    <span>Administração</span>
                    <svg :class="{ 'rotate-180': !isOpen }" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                </button>

                <div x-show="isOpen" x-transition class="space-y-1">

                    <a href="{{ route('usuarios.index') }}" wire:navigate class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('usuarios*') ? 'bg-gray-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('usuarios*') ? 'text-blue-600' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <span class="font-medium text-sm">Usuários</span>
                    </a>

                    <a href="{{ route('controles.index') }}" wire:navigate class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('controles*') ? 'bg-gray-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('controles*') ? 'text-blue-600' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <span class="font-medium text-sm">Controles de Atividades</span>
                    </a>

                    <a href="{{ route('auditoria.humana') }}" wire:navigate class="{{ request()->routeIs('auditoria.humana') ? 'bg-indigo-50 text-indigo-600' : 'text-gray-600 hover:bg-gray-50' }} flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors mt-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        <span class="font-medium text-sm">Auditoria Humana</span>
                    </a>
                    
                </div>
            </div>        
            @endrole
        </nav>
    </aside>

    <header class="fixed top-0 right-0 left-0 md:left-64 h-20 bg-white border-b border-gray-200 z-40 flex items-center justify-between px-4 sm:px-6">
        
        <div class="flex items-center md:hidden">
            <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none transition">
                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                    <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="hidden md:flex"></div>

        <div class="flex items-center">
            <x-dropdown align="right" width="48">
                <x-slot name="trigger">
                    <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                        <div x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>

                        <div class="ms-1">
                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </button>
                </x-slot>

                <x-slot name="content">
                    <x-dropdown-link :href="route('profile')" wire:navigate>
                        {{ __('Meu Perfil') }}
                    </x-dropdown-link>

                    <button wire:click="logout" class="w-full text-start">
                        <x-dropdown-link>
                            {{ __('Sair') }}
                        </x-dropdown-link>
                    </button>
                </x-slot>
            </x-dropdown>
        </div>
    </header>

    <div x-show="open" class="fixed inset-0 bg-white bg-opacity-50 z-40 md:hidden" @click="open = false" style="display: none;"></div>
    
    <!-- MENU MOBILE -->
    <aside :class="{'translate-x-0': open, '-translate-x-full': ! open}" class="fixed inset-y-0 left-0 w-64 bg-white border-r border-gray-200 z-50 transform transition-transform duration-300 md:hidden flex flex-col">
        
        <div class="h-20 flex items-center justify-between px-4 border-b border-gray-200">
            <img src="{{ asset('images/icon-nd.png') }}" alt="Plataforma ND" class="h-10 w-auto object-contain">
            <button @click="open = false" class="text-gray-500 hover:text-gray-700 focus:outline-none">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-2">
            
            <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('dashboard') ? 'bg-gray-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('dashboard') ? 'text-blue-600' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                <span class="font-medium text-sm">Início</span>
            </a>

            <a href="{{ route('servicos.index') }}" wire:navigate class="{{ request()->routeIs('servicos.*') ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                <svg class="{{ request()->routeIs('servicos.*') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-500' }} mr-3 flex-shrink-0 h-5 w-5 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                Serviços
            </a>

            <!-- ADICIONADO A ROLE PROFISSIONAL PARA ABRIR O MENU NO MOBILE -->
            @hasanyrole('admin|manager|administrative|profissional')
            <div x-data="{ isOpen: true }" class="pt-4 pb-1">
                <button @click="isOpen = !isOpen" class="w-full flex items-center justify-between px-3 pb-2 text-xs font-semibold text-gray-500 hover:text-gray-700 transition-colors focus:outline-none">
                    <span>Frequência</span>
                    <svg :class="{ 'rotate-180': !isOpen }" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                </button>

                <div x-show="isOpen" x-transition class="space-y-1">
                    
                    @hasanyrole('admin|manager')
                        <a href="{{ route('relatorios.geral') }}" wire:navigate 
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('relatorios.*') ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
                            <svg class="w-5 h-5 {{ request()->routeIs('relatorios.*') ? 'text-blue-600' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            <span class="font-medium text-sm">Relatórios Gerais</span>
                        </a>
                    @endhasanyrole

                    <!-- VISÍVEL PARA PROFISSIONAIS -->
                    <a href="{{ route('terapias-realizadas.index') }}" wire:navigate class="{{ request()->routeIs('terapias-realizadas.*') ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                        <svg class="{{ request()->routeIs('terapias-realizadas.*') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-500' }} mr-3 flex-shrink-0 h-5 w-5 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                        </svg>
                        Terapias Realizadas
                    </a>

                    <!-- ESCONDIDO DOS PROFISSIONAIS -->
                    @hasanyrole('admin|manager|administrative')
                        <a href="{{ route('ch-solicitada.index') }}" wire:navigate class="{{ request()->routeIs('ch-solicitada.*') ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                            <svg class="{{ request()->routeIs('ch-solicitada.*') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-500' }} mr-3 flex-shrink-0 h-5 w-5 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            CH - Solicitada
                        </a>

                        <a href="{{ route('avaliacoes-neuro.index') }}" wire:navigate class="{{ request()->routeIs('avaliacoes-neuro.*') ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                            <svg class="{{ request()->routeIs('avaliacoes-neuro.*') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-500' }} mr-3 flex-shrink-0 h-5 w-5 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            Avaliações Neuro
                        </a>
                    @endhasanyrole

                </div>
            </div>
            @endhasanyrole

            @hasanyrole('admin|manager|administrative|coordinator|supervisor')
            <div x-data="{ isOpen: true }" class="pt-4 pb-1">
                <button @click="isOpen = !isOpen" class="w-full flex items-center justify-between px-3 pb-2 text-xs font-semibold text-gray-500 hover:text-gray-700 transition-colors focus:outline-none">
                    <span>Coordenação / Supervisão</span>
                    <svg :class="{ 'rotate-180': !isOpen }" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                </button>

                <div x-show="isOpen" x-transition class="space-y-1">

                    <a href="{{ route('acompanhamentos.index') }}" wire:navigate class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('acompanhamentos*') ? 'bg-gray-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('acompanhamentos*') ? 'text-blue-600' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <span class="font-medium text-sm">Acompanhamentos</span>
                    </a>

                    <a href="{{ route('cronograma.index') }}" wire:navigate class="{{ request()->routeIs('cronograma.*') ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors mt-1">
                        <svg class="{{ request()->routeIs('cronograma.*') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-500' }} mr-3 flex-shrink-0 h-5 w-5 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                        </svg>
                        Cronograma de Terapias ABA/DENVER
                    </a>
                    
                    @hasanyrole('admin|manager|administrative')
                    <a href="{{ route('vinculos.index') }}" wire:navigate class="{{ request()->routeIs('vinculos.*') ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors mt-1">
                        <svg class="{{ request()->routeIs('vinculos.*') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-500' }} mr-3 flex-shrink-0 h-5 w-5 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        Vínculos de Pacientes
                    </a>
                    @endhasanyrole
                </div>
            </div>
            @endhasanyrole

            <div x-data="{ isOpen: true }" class="pt-4 pb-1">
                <button @click="isOpen = !isOpen" class="w-full flex items-center justify-between px-3 pb-2 text-xs font-semibold text-gray-500 hover:text-gray-700 transition-colors focus:outline-none">
                    <span>Ocupação</span>
                    <svg :class="{ 'rotate-180': !isOpen }" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                </button>

                <div x-show="isOpen" x-transition class="space-y-1">

                    <a href="{{ route('pacientes.index') }}" wire:navigate class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('pacientes*') ? 'bg-gray-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('pacientes*') ? 'text-blue-600' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <span class="font-medium text-sm">Pacientes</span>
                    </a>
                    
                    <a href="{{ route('agenda-profissionais.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50 transition-colors">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span class="font-medium text-sm">Agenda - Profissionais</span>
                    </a>
                    
                    @hasanyrole('admin|manager|administrative')
                    <a href="{{ route('profissionais.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('profissionais*') ? 'bg-gray-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('profissionais*') ? 'text-blue-600' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="font-medium text-sm">Profissionais</span>
                    </a>
                    @endhasanyrole
                </div>
            </div>

            @role('admin')
            <div x-data="{ isOpen: true }" class="pt-4 pb-1">
                <button @click="isOpen = !isOpen" class="w-full flex items-center justify-between px-3 pb-2 text-xs font-semibold text-gray-500 hover:text-gray-700 transition-colors focus:outline-none">
                    <span>Administração</span>
                    <svg :class="{ 'rotate-180': !isOpen }" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                </button>

                <div x-show="isOpen" x-transition class="space-y-1">

                    <a href="{{ route('usuarios.index') }}" wire:navigate class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('usuarios*') ? 'bg-gray-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('usuarios*') ? 'text-blue-600' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <span class="font-medium text-sm">Usuários</span>
                    </a>

                    <a href="{{ route('controles.index') }}" wire:navigate class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('controles*') ? 'bg-gray-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('controles*') ? 'text-blue-600' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <span class="font-medium text-sm">Controles de Atividades</span>
</a>
                    
                    <a href="{{ route('auditoria.humana') }}" wire:navigate class="{{ request()->routeIs('auditoria.humana') ? 'bg-indigo-50 text-indigo-600' : 'text-gray-600 hover:bg-gray-50' }} flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors mt-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        <span class="font-medium text-sm">Auditoria Humana</span>
                    </a>
                </div>
            </div>        
            @endrole
            
        </nav>
    </aside>
</div>