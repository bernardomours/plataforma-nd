<div>
    <div class="mb-6 flex justify-between items-end">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Olá, {{ explode(' ', auth()->user()->name)[0] }}!</h1>
            <p class="text-sm text-gray-500 mt-1">Bem-vindo(a) ao painel de controle de Produção e RH.</p>
        </div>
        <div class="text-sm font-medium text-gray-500 bg-white px-4 py-2 rounded-lg border border-gray-200 shadow-sm">
            Mês de Apuração: <span class="text-blue-600 font-bold">{{ \Carbon\Carbon::now()->translatedFormat('F / Y') }}</span>
        </div>
    </div>

    <!-- KPIs do RH -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-2">Total Estimado (Mês)</h3>
            <p class="text-2xl font-bold text-gray-900">R$ 0,00</p>
            <p class="text-xs text-gray-400 mt-2 flex items-center gap-1">
                <svg class="w-3 h-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                Baseado nos atendimentos
            </p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-2">Sessões Realizadas</h3>
            <p class="text-2xl font-bold text-blue-600">0</p>
            <p class="text-xs text-gray-400 mt-2">Aguardando repasse</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-2">Glosas Pendentes</h3>
            <p class="text-2xl font-bold text-red-500">0</p>
            <p class="text-xs text-gray-400 mt-2">Valores contestados</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-2">Profissionais Ativos</h3>
            <p class="text-2xl font-bold text-indigo-600">0</p>
            <p class="text-xs text-gray-400 mt-2">Com produção este mês</p>
        </div>

    </div>

    <!-- Atalhos Rápidos e Gráfico Placeholder -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Painel de Ações Rápidas -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 lg:col-span-1">
            <h3 class="font-bold text-gray-800 mb-4">Ações Rápidas</h3>
            
            <div class="space-y-3">
                <a href="{{ route('producao.fechamento') }}" wire:navigate class="group flex items-center justify-between p-3 border border-gray-100 rounded-lg hover:bg-blue-50 hover:border-blue-100 transition-colors">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-blue-100 text-blue-600 rounded-md group-hover:bg-blue-200 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <span class="text-sm font-semibold text-gray-700 group-hover:text-blue-700">Apurar Fechamento</span>
                    </div>
                    <svg class="w-4 h-4 text-gray-300 group-hover:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </a>

                <button class="w-full group flex items-center justify-between p-3 border border-gray-100 rounded-lg hover:bg-gray-50 transition-colors">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-gray-100 text-gray-600 rounded-md">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                        </div>
                        <span class="text-sm font-semibold text-gray-700">Registrar Nova Glosa</span>
                    </div>
                </button>
            </div>
        </div>

        <!-- Área de Alertas / Pendências -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 lg:col-span-2 flex flex-col justify-center items-center text-center">
            <div class="p-4 bg-gray-50 rounded-full mb-3">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            </div>
            <h3 class="font-bold text-gray-800">Sem pendências críticas</h3>
            <p class="text-sm text-gray-500 mt-1 max-w-sm">
                Os repasses e regras cadastradas estão operando normalmente. Acesse o "Fechamento Mensal" no menu lateral para visualizar os valores individuais por profissional.
            </p>
        </div>

    </div>
</div>