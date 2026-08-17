<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Define a nova senha e libera o acesso ao sistema.
     *
     * Não pedimos a senha atual de propósito: o usuário acabou de digitá-la no login e
     * foi trazido para cá pelo middleware. Exigi-la de novo só adicionaria atrito num
     * fluxo que já está autenticado.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            $this->reset('password', 'password_confirmation');

            throw $e;
        }

        $user = Auth::user();

        // Impede "trocar" pela mesma senha — sem isto o usuário poderia repetir
        // 'mudar123' e continuar preso no mesmo laço, sem ganho nenhum de segurança.
        if (Hash::check($validated['password'], $user->password)) {
            $this->reset('password', 'password_confirmation');

            throw ValidationException::withMessages([
                'password' => 'A nova senha precisa ser diferente da atual.',
            ]);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ]);

        // Registra a troca na auditoria, sem guardar nada da senha.
        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->event('updated')
            ->withProperties(['attributes' => [
                'acao' => 'Senha padrão substituída no primeiro acesso',
            ]])
            ->log('Senha definida pelo próprio usuário no primeiro acesso');

        session()->regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }

    /**
     * Saída de emergência: usa a mesma ação de logout do restante do sistema (não existe
     * rota nomeada 'logout' — o Breeze com Volt faz isso por ação Livewire).
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect(route('login', absolute: false), navigate: true);
    }
}; ?>

<div class="fixed inset-0 z-50 flex min-h-screen w-full items-center justify-center bg-gray-50 px-6 py-12">
    <div class="w-full max-w-md">

        <div class="mb-8 flex items-center justify-center gap-3">
            <img src="{{ asset('images/icon-nd.png') }}" alt="" class="h-12 w-auto">
            <span class="text-2xl font-extrabold tracking-wider text-blue-900">Plataforma ND</span>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-8 shadow-sm">

            <div class="mb-6 flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <p class="text-sm leading-relaxed text-amber-800">
                    Sua conta ainda está com a <strong>senha padrão</strong>. Defina uma senha
                    pessoal para continuar — ela será exigida nos próximos acessos.
                </p>
            </div>

            <h1 class="text-xl font-bold text-gray-900">Criar nova senha</h1>
            <p class="mt-1 text-sm text-gray-500">
                Olá, {{ Auth::user()->name }}. Escolha uma senha que só você conheça.
            </p>

            <form wire:submit="updatePassword" class="mt-6 space-y-5">

                <div>
                    <x-input-label for="password" :value="__('Nova senha')" />
                    <x-text-input wire:model="password" id="password" type="password"
                                  class="mt-1 block w-full" autocomplete="new-password" autofocus required />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    <p class="mt-1.5 text-xs text-gray-500">Mínimo de 8 caracteres.</p>
                </div>

                <div>
                    <x-input-label for="password_confirmation" :value="__('Confirmar nova senha')" />
                    <x-text-input wire:model="password_confirmation" id="password_confirmation" type="password"
                                  class="mt-1 block w-full" autocomplete="new-password" required />
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                </div>

                <button type="submit" wire:loading.attr="disabled" wire:target="updatePassword"
                        class="flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-3 text-sm font-bold text-white transition-colors hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60">
                    <svg wire:loading wire:target="updatePassword" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                        <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="updatePassword">Salvar e continuar</span>
                    <span wire:loading wire:target="updatePassword">Salvando...</span>
                </button>
            </form>
        </div>

        {{-- Saída de emergência, para ninguém ficar preso caso tenha entrado com a conta
             errada. Funciona porque o middleware deixa passar as chamadas do Livewire. --}}
        <div class="mt-6 text-center">
            <button type="button" wire:click="logout" class="text-sm text-gray-500 hover:text-gray-900">
                Sair e entrar com outra conta
            </button>
        </div>
    </div>
</div>
