<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Plataforma ND') }} - Produção</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-gray-50 text-gray-900">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen flex w-full">
        
        <aside :class="{'translate-x-0': sidebarOpen, '-translate-x-full': !sidebarOpen}" class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 flex flex-col transition-transform duration-300 md:translate-x-0 md:static md:flex-shrink-0">
            <div class="h-20 flex items-center justify-center border-b border-gray-200 px-4">
                <a href="{{ route('producao.index') }}" wire:navigate>
                    <img src="{{ asset('images/icon-nd.png') }}" class="h-12 w-auto object-contain" alt="Logo">
                </a>
                <span class="ml-2 text-xs font-bold text-blue-600 bg-blue-50 px-2 py-1 rounded">PRODUÇÃO</span>
            </div>

            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-2">
    <!-- Dashboard Principal -->
            <a href="{{ route('producao.index') }}" wire:navigate class="{{ request()->routeIs('producao.index') ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }} flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                <span class="font-medium text-sm">Painel Inicial</span>
            </a>

            <div class="pt-4 pb-2">
                <p class="px-3 text-xs font-bold tracking-wider text-gray-400 uppercase">Apuração</p>
            </div>

                <!-- Fechamento Mensal (A rota que criamos!) -->
                <a href="{{ route('producao.fechamento') }}" wire:navigate class="{{ request()->routeIs('producao.fechamento') ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }} flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    <span class="font-medium text-sm">Fechamento Mensal</span>
                </a>

                <a href="{{ route('producao.auditoria') }}" wire:navigate class="{{ request()->routeIs('producao.auditoria') ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }} flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                    <span class="font-medium text-sm">Auditoria de Atendimentos</span>
                </a>
                @hasanyrole('admin|manager')
                <a href="{{ route('producao.glosas') }}" wire:navigate class="{{ request()->routeIs('producao.glosas') ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }} flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span class="font-medium text-sm">Relatórios de Glosa</span>
                </a>
                @endhasanyrole

                <div class="pt-4 pb-2">
                    <p class="px-3 text-xs font-bold tracking-wider text-gray-400 uppercase">Configurações</p>
                </div>

                <!-- Regras de Repasse (Exemplo para o futuro) -->
                <a href="{{ route('producao.regras') }}" wire:navigate class="{{ request()->routeIs('producao.regras') ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }} flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    <span class="font-medium text-sm">Regras de Pagamento</span>
                </a>
            </nav>
        </aside>

        <div class="flex-1 flex flex-col min-w-0">
            <header class="h-20 bg-white border-b border-gray-200 flex items-center justify-between px-4 sm:px-6">
                <button @click="sidebarOpen = true" class="md:hidden text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                
                <div class="flex-1"></div> 
                
                <!-- NOVO CONTAINER DO USUÁRIO COM DROPDOWN -->
                <div class="flex items-center gap-4">
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false" @close.stop="open = false">
                        
                        <!-- Botão de Ativação -->
                        <button @click="open = ! open" class="flex items-center gap-2 text-sm font-bold text-gray-700 hover:text-blue-600 focus:outline-none transition-colors">
                            <span>{{ auth()->user()->name }}</span>
                            
                            <!-- Ícone de Chevron (seta) -->
                            <svg class="h-4 w-4 text-gray-500" :class="{'rotate-180': open}" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="transition: transform 0.2s;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div x-show="open"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute right-0 z-50 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 py-1 border border-gray-200"
                             style="display: none;">

                            <a href="{{ url('/') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-blue-600 transition-colors">
                                Área da Clínica
                            </a>

                            <div class="border-t border-gray-100 my-1"></div>

                            <form method="POST" action="{{ route('producao.sair') }}" class="m-0 p-0">
                                @csrf
                                <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-red-600 font-medium hover:bg-red-50 hover:text-red-700 transition-colors">
                                    Sair
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

            </header>

            <main class="flex-1 overflow-y-auto bg-gray-50 p-4 sm:p-6 lg:p-8">
                {{ $slot }}
            </main>
        </div>

        <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-gray-900 bg-opacity-50 md:hidden" style="display: none;"></div>
    </div>
    @livewireScripts
</body>
</html>