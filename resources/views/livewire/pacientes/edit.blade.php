<div>
    <div x-data="{ show: @entangle('showModal') }" 
         x-show="show" 
         x-cloak
         style="display: none;" 
         class="relative z-50" 
         aria-labelledby="modal-title" 
         role="dialog" 
         aria-modal="true">
        
        <div x-show="show" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0" 
             x-transition:enter-end="opacity-100" 
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100" 
             x-transition:leave-end="opacity-0" 
             class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity backdrop-blur-sm"></div>

        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                
                <div x-show="show" 
                     x-transition:enter="ease-out duration-300" 
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                     x-transition:leave="ease-in duration-200" 
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                     @click.outside="$wire.fecharModal()"
                     class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-4xl border border-gray-200">
                    
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <h3 class="text-lg font-bold text-gray-900" id="modal-title">Editar Cadastro do Paciente</h3>
                        <button type="button" wire:click="fecharModal" class="text-gray-400 hover:text-gray-500 focus:outline-none transition-colors">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form wire:submit.prevent="update">
                        
                        <div class="px-6 py-6 space-y-6 max-h-[65vh] overflow-y-auto">
                            
                            @if (session()->has('message'))
                                <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center gap-2">
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    <span class="font-medium text-sm">{{ session('message') }}</span>
                                </div>
                            @endif

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Nome <span class="text-red-500">*</span></label>
                                    <input wire:model="name" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Data de Nascimento <span class="text-red-500">*</span></label>
                                    <input wire:model="birth_date" type="date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    @error('birth_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">CPF <span class="text-red-500">*</span></label>
                                    <input wire:model="cpf" type="text" maxlength="14" x-data="{ formatCpf() { let value = $el.value.replace(/\D/g, ''); value = value.replace(/(\d{3})(\d)/, '$1.$2'); value = value.replace(/(\d{3})(\d)/, '$1.$2'); value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2'); $el.value = value; } }" x-on:input="formatCpf()" placeholder="000.000.000-00" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    @error('cpf') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Carteira <span class="text-red-500">*</span></label>
                                    <input wire:model="agreement_number" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    @error('agreement_number') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Nome do Responsável</label>
                                    <input wire:model="guardian_name" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Contato do Responsável</label>
                                    <input wire:model="guardian_phone" type="tel" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>

                            <hr class="border-gray-200">

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Unidade <span class="text-red-500">*</span></label>
                                    <select wire:model.live="unit_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Selecione a Unidade</option>
                                        @foreach($units as $unit)
                                            <option value="{{ $unit->id }}">{{ $unit->city }}</option>
                                        @endforeach
                                    </select>
                                    @error('unit_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Convênio <span class="text-red-500">*</span></label>
                                    <select wire:model="agreement_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Selecione o Convênio</option>
                                        @foreach($agreements as $agreement)
                                            <option value="{{ $agreement->id }}">{{ $agreement->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('agreement_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
                                <h3 class="font-bold text-gray-800 mb-4">Equipe de Acompanhamento (Supervisão e Coordenação)</h3>
                                
                                @foreach($patientServices as $index => $service)
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end mb-4 bg-white p-4 rounded border border-gray-100 shadow-sm relative">
                                        
                                        @if(count($patientServices) > 1)
                                            <button type="button" wire:click="removeService({{ $index }})" class="absolute top-2 right-2 text-red-500 hover:text-red-700">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            </button>
                                        @endif

                                        <div class="col-span-1 md:col-span-1">
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Ambiente / Serviço *</label>
                                            <select wire:model="patientServices.{{ $index }}.service_type_id" class="block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500">
                                                <option value="">Selecione</option>
                                                @foreach($serviceTypes as $type)
                                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                                @endforeach
                                            </select>
                                            @error("patientServices.$index.service_type_id") <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                        </div>

                                        <div class="col-span-1 md:col-span-1">
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Coordenador</label>
                                            <select wire:model="patientServices.{{ $index }}.coordinator_id" class="block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500" {{ empty($coordinators) ? 'disabled' : '' }}>
                                                <option value="">{{ empty($coordinators) ? 'Selecione a Unidade antes' : 'Selecione' }}</option>
                                                @foreach($coordinators as $coord)
                                                    <option value="{{ $coord->id }}">{{ $coord->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-span-1 md:col-span-1">
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Supervisor</label>
                                            <select wire:model="patientServices.{{ $index }}.supervisor_id" class="block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500" {{ empty($supervisors) ? 'disabled' : '' }}>
                                                <option value="">{{ empty($supervisors) ? 'Selecione a Unidade antes' : 'Selecione' }}</option>
                                                @foreach($supervisors as $sup)
                                                    <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                @endforeach

                                <button type="button" wire:click="addService" class="mt-2 text-sm font-semibold text-blue-600 hover:text-blue-800 flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                    Adicionar Nova Supervisão/Coordenação
                                </button>
                            </div>
                        </div>

                        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex items-center justify-end gap-3 rounded-b-xl">
                            <button type="button" wire:click="fecharModal" class="px-5 py-2 bg-white border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none transition-colors">
                                Cancelar
                            </button>
                            <button type="submit" class="px-5 py-2 bg-blue-600 border border-transparent rounded-lg text-sm font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                                Salvar alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>