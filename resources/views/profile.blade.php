<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3 py-3">
            <div class="p-2 bg-blue-50 text-blue-600 rounded-lg border border-blue-100 mt-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
            </div>
            <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                Meu Perfil
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <div class="p-4 sm:p-8 bg-white shadow-sm sm:rounded-xl border border-gray-200 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-1.5 h-full bg-blue-500"></div>
                
                <div class="max-w-2xl pl-2">
                    <livewire:profile.update-profile-information-form />
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow-sm sm:rounded-xl border border-gray-200 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-1.5 h-full bg-indigo-500"></div>
                
                <div class="max-w-2xl pl-2">
                    <livewire:profile.update-password-form />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>