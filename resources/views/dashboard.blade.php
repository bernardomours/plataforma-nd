<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- SAUDAÇÃO UNIVERSAL (Todos veem) -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-200 flex items-center justify-between p-6">
                <div class="text-gray-900 text-lg">
                    👋 Seja bem-vindo(a), <span class="font-bold">{{ auth()->user()->name }}</span>!
                </div>
                <div class="hidden sm:block text-sm font-medium text-gray-500 bg-gray-50 px-3 py-1 rounded-md border border-gray-100">
                    {{ now()->format('d/m/Y') }}
                </div>
            </div>

            <!-- ========================================== -->
            <!-- VISÃO EXCLUSIVA DA CLÍNICA (Admin/Manager) -->
            <!-- ========================================== -->
            @hasanyrole('admin|manager|administrative')
                <div class="mb-2">
                    <h2 class="text-sm font-bold text-gray-500 uppercase tracking-wider">Visão Geral da Clínica</h2>
                </div>
                
                <div class="grid grid-cols-1 gap-6">
                    <!-- Componente de Aniversariantes -->
                    <livewire:dashboard.aniversariantes />
                </div>
            @endhasanyrole


            <!-- ========================================== -->
            <!-- VISÃO EXCLUSIVA DO PROFISSIONAL (Terapeutas)-->
            <!-- ========================================== -->
            @role('profissional')
                <div class="mb-2 mt-8">
                    <h2 class="text-sm font-bold text-gray-500 uppercase tracking-wider">Meu Ambiente de Trabalho</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <div class="bg-white shadow-sm sm:rounded-xl border border-gray-200 flex flex-col h-full">
                        <div class="p-4 border-b border-gray-100 bg-gray-50/50 rounded-t-xl flex items-center justify-between">
                            <h3 class="font-bold text-gray-800 flex items-center gap-2">
                                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                Minha Agenda (Hoje)
                            </h3>
                        </div>
                        
                        <div class="p-6 flex-1 flex flex-col">
                            <p class="text-sm text-gray-500 mb-3">Para visualizar sua agenda semanal, acesse "Agenda - Profissionais"</p>
                            <livewire:profissionais.minha-agenda />
                        </div>
                    </div>

                    <div class="bg-white shadow-sm sm:rounded-xl border border-gray-200 flex flex-col h-full">
                        <div class="p-4 border-b border-gray-100 bg-gray-50/50 rounded-t-xl flex items-center justify-between">
                            <h3 class="font-bold text-gray-800 flex items-center gap-2">
                                <span class="text-xl">🎂</span>
                                Aniversariantes do Mês
                            </h3>
                        </div>
                        <div class="p-6 flex-1 flex flex-col">
                            <livewire:profissionais.aniversariantes-pacientes />
                        </div>
                    </div>

                </div>
            @endrole

        </div>
    </div>
</x-app-layout>