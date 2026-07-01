<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Serviços</h1>
        <p class="text-sm text-gray-500 mt-1">Visualizar informações dos serviços disponíveis na Núcleo Desenvolve</p>
    </div>

    <div class="max-w-7xl mx-auto py-8 sm:px-6 lg:px-8">
        
        @if(!$this->canEdit)
            <div class="mb-6 bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span class="text-sm font-medium">Modo de Visualização: Você não tem permissão para alterar as configurações de serviços.</span>
            </div>
        @endif

        <div x-data="{ activeTab: 'unidades' }" class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
            
            <div class="border-b border-gray-200 bg-gray-50/50">
                <nav class="flex -mb-px px-4" aria-label="Tabs">
                    <button @click="activeTab = 'unidades'" :class="activeTab === 'unidades' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors">
                        Unidades
                    </button>
                    <button @click="activeTab = 'terapias'" :class="activeTab === 'terapias' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors">
                        Terapias
                    </button>
                    <button @click="activeTab = 'convenios'" :class="activeTab === 'convenios' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors">
                        Convênios
                    </button>
                </nav>
            </div>

            <div x-show="activeTab === 'unidades'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" style="display: none;">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold text-gray-800">Unidades</h3>
                        @if($this->canEdit)
                            <button class="px-4 py-2 bg-blue-500 text-white text-sm font-semibold rounded-md hover:bg-blue-600 transition-colors">Criar Unidade</button>
                        @endif
                    </div>
                    
                    <div class="overflow-x-auto border border-gray-200 rounded-lg">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50 border-b border-gray-200 text-gray-600">
                                <tr>
                                    <th class="py-3 px-4 font-semibold">Nome</th>
                                    <th class="py-3 px-4 font-semibold">CNPJ</th>
                                    <th class="py-3 px-4 font-semibold">Endereço</th>
                                    <th class="py-3 px-4 font-semibold">Cidade</th>
                                    @if($this->canEdit) <th class="py-3 px-4 font-semibold text-right">Ações</th> @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($units as $unit)
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-3 px-4 font-medium text-gray-900">{{ $unit->name }}</td>
                                        <td class="py-3 px-4 text-gray-500">{{ $unit->cnpj }}</td>
                                        <td class="py-3 px-4 text-gray-500">{{ $unit->street }}, {{ $unit->number }} - {{ $unit->neighborhood }}</td>
                                        <td class="py-3 px-4 text-gray-500">{{ $unit->city }}</td>
                                        @if($this->canEdit)
                                            <td class="py-3 px-4 text-right">
                                                <button class="text-blue-600 hover:text-blue-800 font-medium text-xs">Editar</button>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div x-show="activeTab === 'terapias'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" style="display: none;">
                <div class="p-0 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200 text-gray-600">
                            <tr>
                                <th class="py-3 px-6 font-semibold w-1/4">Terapia</th>
                                @foreach($units as $unit)
                                    <th class="py-3 px-4 font-semibold text-center">{{ $unit->city ?? $unit->name }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($therapies as $therapy)
                                <tr class="hover:bg-gray-50">
                                    <td class="py-4 px-6 font-medium text-gray-900 uppercase text-xs">{{ $therapy->name }}</td>
                                    
                                    @foreach($units as $unit)
                                        <td class="py-4 px-4 text-center">
                                            <label class="relative inline-flex items-center {{ $this->canEdit ? 'cursor-pointer' : 'cursor-not-allowed opacity-60' }}">
                                                <input type="checkbox" value="" class="sr-only peer" 
                                                    wire:click="toggleTherapyUnit({{ $therapy->id }}, {{ $unit->id }})"
                                                    {{ $therapy->units->contains($unit->id) ? 'checked' : '' }}
                                                    {{ !$this->canEdit ? 'disabled' : '' }}>
                                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                            </label>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div x-show="activeTab === 'convenios'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" style="display: none;">
                <div class="p-0 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200 text-gray-600">
                            <tr>
                                <th class="py-3 px-6 font-semibold w-1/4">Convênio</th>
                                @foreach($units as $unit)
                                    <th class="py-3 px-4 font-semibold text-center">{{ $unit->city ?? $unit->name }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($agreements as $agreement)
                                <tr class="hover:bg-gray-50">
                                    <td class="py-4 px-6 font-medium text-gray-900 uppercase text-xs">{{ $agreement->name }}</td>
                                    
                                    @foreach($units as $unit)
                                        <td class="py-4 px-4 text-center">
                                            <label class="relative inline-flex items-center {{ $this->canEdit ? 'cursor-pointer' : 'cursor-not-allowed opacity-60' }}">
                                                <input type="checkbox" value="" class="sr-only peer" 
                                                    wire:click="toggleAgreementUnit({{ $agreement->id }}, {{ $unit->id }})"
                                                    {{ $agreement->units->contains($unit->id) ? 'checked' : '' }}
                                                    {{ !$this->canEdit ? 'disabled' : '' }}>
                                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                            </label>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>