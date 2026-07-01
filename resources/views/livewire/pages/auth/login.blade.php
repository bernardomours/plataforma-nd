<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\Attributes\Url;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    #[Url]
    public string $context = '';

    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        if ($this->context === 'producao') {
            $this->redirect(route('producao.index', absolute: false), navigate: true);
        } else {
            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
        }
    }
}; ?>

<div class="fixed inset-0 z-50 flex min-h-screen w-full bg-white">
        
    <div class="hidden lg:flex w-1/2 bg-gradient-to-br from-blue-700 via-blue-800 to-indigo-900 relative items-center justify-center overflow-hidden">
        <div class="text-center z-10 px-12">
            <div class="w-full max-w-md mx-auto mb-8 bg-white/10 p-8 rounded-2xl backdrop-blur-sm border border-white/20 shadow-2xl">
                <img src="{{ asset('images/icon-nd.png') }}" alt="Plataforma ND" class="h-200 w-auto">
            </div>
            <h2 class="text-4xl font-extrabold text-white mb-4">Bem-Vindo(a) à Plataforma ND</h2>
            <p class="text-blue-100 text-lg">Gestão e organização da clínica Núcleo Desenvolve</p>
        </div>

        <div class="absolute -bottom-32 -left-40 w-96 h-96 bg-blue-500 rounded-full mix-blend-multiply filter blur-3xl opacity-50"></div>
        <div class="absolute -top-32 -right-40 w-96 h-96 bg-indigo-500 rounded-full mix-blend-multiply filter blur-3xl opacity-50"></div>
    </div>

    <div class="w-full lg:w-1/2 flex items-center justify-center p-8 sm:p-12 md:p-24 bg-gray-50 lg:bg-white overflow-y-auto">
        
        <div class="w-full max-w-md">
            
            <div class="flex lg:hidden items-center justify-center gap-3 mb-10">
                <img src="{{ asset('images/icon-nd.png') }}" alt="Plataforma ND" class="h-12 w-auto">
                <span class="text-blue-900 font-extrabold text-2xl tracking-wider">Plataforma ND</span>
            </div>

            @if(request()->query('context') === 'producao')
                <h1 class="text-3xl font-bold text-indigo-700 mb-2">Área da Produção</h1>
                <p class="text-gray-500 mb-8">Identifique-se para acessar os relatórios de produção.</p>
            @else
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Bem-vindo(a)!</h1>
                <p class="text-gray-500 mb-8">Insira as suas credenciais para acessar o sistema.</p>
            @endif

            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form wire:submit="login" class="space-y-6">
                
                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">E-mail</label>
                    <input wire:model="form.email" id="email" type="email" required autofocus autocomplete="username" 
                        class="w-full px-4 py-3 rounded-lg bg-gray-50 border border-gray-200 text-gray-900 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all shadow-sm" 
                        placeholder="seu@email.com">
                    <x-input-error :messages="$errors->get('form.email')" class="mt-2 text-red-500 text-sm" />
                </div>

                <div x-data="{ showPassword: false }">
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Senha</label>
                    <div class="relative">
                        <input wire:model="form.password" id="password" x-bind:type="showPassword ? 'text' : 'password'" required autocomplete="current-password" 
                            class="w-full px-4 py-3 pr-12 rounded-lg bg-gray-50 border border-gray-200 text-gray-900 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all shadow-sm" 
                            placeholder="••••••••">
                        
                        <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-blue-600 focus:outline-none transition-colors">
                            
                            <svg x-show="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>

                            <svg x-show="showPassword" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            </svg>
                        </button>
                    </div>
                    <x-input-error :messages="$errors->get('form.password')" class="mt-2 text-red-500 text-sm" />
                </div>

                <div class="flex items-center justify-between">
                    <label for="remember" class="inline-flex items-center cursor-pointer">
                        <input wire:model="form.remember" id="remember" type="checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                        <span class="ms-2 text-sm text-gray-600 font-medium">Lembrar de mim</span>
                    </label>

                    @if (Route::has('password.request'))
                        <a class="text-sm font-semibold text-blue-600 hover:text-blue-800 transition-colors" href="{{ route('password.request') }}" wire:navigate>
                            Esqueceu a senha?
                        </a>
                    @endif
                </div>

                <div>
                    <button type="submit" class="w-full flex justify-center items-center py-3.5 px-4 border border-transparent rounded-lg shadow-md text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors uppercase tracking-wide">
                        <span wire:loading.remove wire:target="login">Entrar no Sistema</span>
                        <span wire:loading wire:target="login">Processando...</span>
                    </button>
                </div>
            </form>

            <div class="mt-8 flex items-center justify-center">
                <div class="border-t border-gray-200 flex-grow"></div>
                <span class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Acesso Restrito</span>
                <div class="border-t border-gray-200 flex-grow"></div>
            </div>

            <div class="mt-8">
                @if($context === 'producao' || request()->query('context') === 'producao')
                    <a href="{{ route('login') }}" wire:navigate class="w-full flex justify-center items-center gap-2 py-3 px-4 border-2 border-gray-200 rounded-lg text-sm font-bold text-gray-700 bg-white hover:border-blue-500 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        Voltar à Plataforma ND
                    </a>
                @else
                    <a href="{{ route('login', ['context' => 'producao']) }}" wire:navigate class="w-full flex justify-center items-center gap-2 py-3 px-4 border-2 border-gray-200 rounded-lg text-sm font-bold text-gray-700 bg-white hover:border-indigo-500 hover:text-indigo-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Acessar Área da Produção
                    </a>
                @endif
            </div>

        </div>
    </div>
</div>