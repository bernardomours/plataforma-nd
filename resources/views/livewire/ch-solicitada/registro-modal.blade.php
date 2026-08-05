<div x-data="{ open: $wire.entangle('isModalOpen') }" 
     x-show="open" 
     style="display: none;" 
     class="fixed inset-0 z-50 overflow-y-auto" 
     aria-labelledby="modal-title" 
     role="dialog" 
     aria-modal="true">
    
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        
        <div x-show="open"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-600 bg-opacity-75 transition-opacity backdrop-blur-sm" 
             wire:click="closeModal"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div x-show="open"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
            
            <form wire:submit.prevent="saveRecord">
                <div class="bg-white px-6 pt-6 pb-6">
                    <div class="flex justify-between items-center mb-4 border-b pb-3">
                        <h3 class="text-lg leading-6 font-bold text-gray-900" id="modal-title">
                            Nova Solicitação CH
                        </h3>
                        <button type="button" wire:click="closeModal" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                            <span class="sr-only">Fechar</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    @if($patient)
                        <div class="mb-4 bg-blue-50 p-3 rounded-md border border-blue-100">
                            <p class="text-sm text-blue-800 font-semibold uppercase">Paciente: {{ $patient->name }}</p>
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Mês/Ano <span class="text-red-500">*</span></label>
                            <input type="month" wire:model="month_year" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            @error('month_year') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Número da Requisição <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="requisition_number" placeholder="Ex: REQ-2026-001" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            @error('requisition_number') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="space-y-4">
                        <h4 class="text-sm font-semibold text-gray-700 border-b pb-2 mb-4">Terapias Solicitadas</h4>
                        
                        @foreach($terapias as $index => $terapia)
                            <div wire:key="terapia-{{ $index }}" class="bg-gray-50 p-4 rounded-lg border border-gray-200 relative">
                                @if(count($terapias) > 1)
                                    <button type="button" wire:click="removerTerapia({{ $index }})" class="absolute top-2 right-2 text-red-500 hover:text-red-700" title="Remover Terapia">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                @endif

                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Terapia <span class="text-red-500">*</span></label>
                                        <select wire:model="terapias.{{ $index }}.therapy_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            <option value="">Selecione a Terapia</option>
                                            @foreach($therapies as $therapy)
                                                <option value="{{ $therapy->id }}">{{ $therapy->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('terapias.'.$index.'.therapy_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Tipo de Atendimento <span class="text-red-500">*</span></label>
                                        <select wire:model="terapias.{{ $index }}.service_type_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            <option value="">Selecione o Tipo</option>
                                            @foreach($serviceTypes as $type)
                                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('terapias.'.$index.'.service_type_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div class="grid grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">CH Solicitada <span class="text-red-500">*</span></label>
                                        <input type="number" step="0.01" wire:model="terapias.{{ $index }}.requested_hours" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        @error('terapias.'.$index.'.requested_hours') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">CH Liberada</label>
                                        <input type="number" step="0.01" wire:model="terapias.{{ $index }}.approved_hours" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm bg-white">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">CH Planejada</label>
                                        <input type="number" step="0.01" wire:model="terapias.{{ $index }}.planned_hours" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm bg-white">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <button type="button" wire:click="adicionarTerapia" class="mt-4 flex items-center text-sm text-blue-600 font-semibold hover:text-blue-800 focus:outline-none">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Adicionar outra Terapia
                    </button>
                </div>
                
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end gap-3 rounded-b-xl">
                    <button type="button" wire:click="closeModal" class="px-4 py-2 border border-gray-300 text-gray-700 bg-white rounded-md hover:bg-gray-100 text-sm font-semibold transition-colors shadow-sm focus:outline-none">
                        Cancelar
                    </button>
                    
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-md hover:bg-blue-700 transition-colors shadow-sm focus:outline-none">
                        Salvar Solicitação
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>