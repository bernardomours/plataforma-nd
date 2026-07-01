<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
                    Editar Avaliação Neuro
                </h2>
                <div class="text-sm text-gray-500 mt-1 flex items-center gap-2">
                    <a href="{{ route('avaliacoes-neuro.index') }}" wire:navigate class="hover:text-blue-600">Avaliações Neuro</a>
                    <span>></span>
                    <span>Avaliação Neuro</span>
                    <span>></span>
                    <span>Editar</span>
                </div>
            </div>
            
            <button wire:click="deleteAssessment" wire:confirm="Tem certeza que deseja excluir esta avaliação inteira?" class="px-4 py-2 bg-red-600 text-white rounded-lg font-semibold text-sm hover:bg-red-700 transition-colors shadow-sm">
                Excluir
            </button>
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        
        @if (session()->has('message'))
            <div class="p-4 bg-green-50 text-green-700 rounded-lg border border-green-200">
                {{ session('message') }}
            </div>
        @endif

        <div class="bg-white shadow-sm sm:rounded-xl border border-gray-200">
            <div class="p-6 border-b border-gray-100">
                <h3 class="text-lg font-bold text-gray-900">Informações da Avaliação</h3>
                <p class="text-sm text-gray-500">Defina o paciente e o profissional responsável pelas 10 sessões.</p>
            </div>

            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Paciente <span class="text-red-500">*</span></label>
                        <select wire:model="patient_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach($pacientes as $paciente)
                                <option value="{{ $paciente->id }}">{{ $paciente->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Profissional Responsável <span class="text-red-500">*</span></label>
                        <select wire:model="professional_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach($profissionais as $profissional)
                                <option value="{{ $profissional->id }}">{{ $profissional->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Status da Avaliação <span class="text-red-500">*</span></label>
                        <select wire:model="status" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="Em andamento">Em andamento</option>
                            <option value="Concluída">Concluída</option>
                            <option value="Cancelada">Cancelada</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Sessão Atual <span class="text-red-500">*</span></label>
                        <input wire:model="current_session" type="text" class="w-full rounded-lg border-gray-300 shadow-sm bg-gray-50 text-gray-500" disabled>
                        <p class="text-xs text-gray-500 mt-1">Avança de 1 a 10 automaticamente.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button wire:click="updateAssessment" class="px-5 py-2.5 bg-blue-400 text-white rounded-lg font-bold shadow-sm hover:bg-blue-500 transition-colors">
                Salvar alterações
            </button>
            <a href="{{ route('avaliacoes-neuro.index') }}" wire:navigate class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-lg font-bold hover:bg-gray-50 transition-colors">
                Cancelar
            </a>
        </div>

        <div class="bg-white shadow-sm sm:rounded-xl border border-gray-200 mt-8">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-900">Diário de Sessões</h3>
                
                @if($podeAdicionarSessao)
                    <button wire:click="openSessionModal" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 text-blue-700 border border-blue-200 rounded-lg font-semibold text-sm hover:bg-blue-100 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Registrar Nova Sessão
                    </button>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="py-4 px-6 font-semibold text-gray-700">Sessão</th>
                            <th class="py-4 px-6 font-semibold text-gray-700">Data</th>
                            <th class="py-4 px-6 font-semibold text-gray-700">Profissional</th>
                            <th class="py-4 px-6 font-semibold text-gray-700">Observações</th>
                            <th class="py-4 px-6 font-semibold text-gray-700 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($sessoes as $sessao)
                            <tr class="hover:bg-gray-50">
                                <td class="py-3 px-6">
                                    <span class="px-2 py-1 bg-blue-50 text-blue-600 font-semibold text-xs border border-blue-100 rounded">
                                        {{ $sessao->session_number }}ª Sessão
                                    </span>
                                </td>
                                <td class="py-3 px-6 text-gray-600">{{ $sessao->date->format('d/m/Y') }}</td>
                                <td class="py-3 px-6 text-gray-800">{{ $sessao->professional->name }}</td>
                                <td class="py-3 px-6 text-gray-500 max-w-xs truncate" title="{{ $sessao->observations }}">
                                    {{ Str::limit($sessao->observations, 40) }}
                                </td>
                                <td class="py-3 px-6 text-right">
                                    <button wire:click="openSessionModal({{ $sessao->id }})" class="text-blue-600 hover:text-blue-800 font-semibold mr-3 text-sm">
                                        Editar
                                    </button>
                                    <button wire:click="deleteSession({{ $sessao->id }})" wire:confirm="Excluir esta sessão?" class="text-red-600 hover:text-red-800 font-semibold text-sm">
                                        Excluir
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-gray-400">Nenhuma sessão registrada.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($showSessionModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50 p-4">
            <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full border border-gray-200 overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h3 class="text-lg font-bold text-gray-900">{{ $editingSessionId ? 'Editar Sessão' : 'Registrar Nova Sessão' }}</h3>
                    <button wire:click="closeSessionModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Número da Sessão</label>
                            <input wire:model="session_number" type="number" min="1" max="10" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                            @error('session_number') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Data da Sessão</label>
                            <input wire:model="session_date" type="date" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                            @error('session_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Profissional Atendente</label>
                        <select wire:model="session_professional_id" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                            @foreach($profissionais as $profissional)
                                <option value="{{ $profissional->id }}">{{ $profissional->name }}</option>
                            @endforeach
                        </select>
                        @error('session_professional_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Observações e Anotações</label>
                        <textarea wire:model="session_observations" rows="4" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"></textarea>
                    </div>
                </div>

                <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                    <button wire:click="closeSessionModal" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-100">
                        Cancelar
                    </button>
                    <button wire:click="saveSession" class="px-4 py-2 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700">
                        Salvar Sessão
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>