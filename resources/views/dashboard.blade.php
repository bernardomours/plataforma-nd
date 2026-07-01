<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-200">
                <div class="p-6 text-gray-900 text-lg">
                    👋 Seja bem-vindo(a), <span class="font-bold">{{ auth()->user()->name }}</span>!
                </div>
            </div>

            <livewire:dashboard.aniversariantes />

            </div>
    </div>
</x-app-layout>