<div>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
            Registrar Avaliação Neuro
        </h2>
        <div class="text-sm text-gray-500 mt-1 flex items-center gap-2">
            <a href="{{ route('avaliacoes-neuro.index') }}" wire:navigate class="hover:text-blue-600">Avaliações Neuro</a>
            <span>></span>
            <span>Registrar</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-xl border border-gray-200">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900">Informações Iniciais</h3>
                    <p class="text-sm text-gray-500">Selecione o paciente e o profissional responsável para iniciar as 10 sessões.</p>
                </div>

                <form wire:submit="save" class="p-6 space-y-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Paciente <span class="text-red-500">*</span></label>
                        <select wire:model="patient_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                            <option value="">Selecione um paciente...</option>
                            @foreach($pacientes as $paciente)
                                <option value="{{ $paciente->id }}">{{ $paciente->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Profissional Responsável <span class="text-red-500">*</span></label>
                        <select wire:model="professional_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                            <option value="">Selecione um profissional...</option>
                            @foreach($profissionais as $profissional)
                                <option value="{{ $profissional->id }}">{{ $profissional->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center gap-3 pt-4">
                        <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg font-semibold hover:bg-blue-600 transition-colors">
                            Criar e Acessar Diário
                        </button>
                        <a href="{{ route('avaliacoes-neuro.index') }}" wire:navigate class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-50 transition-colors">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>