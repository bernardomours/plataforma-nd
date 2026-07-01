<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar Paciente</h2>
    </x-slot>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <form wire:submit.prevent="update" class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 p-6 space-y-6">
             @if (session()->has('message'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" 
                    class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg relative flex items-center gap-2" role="alert">
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span class="block sm:inline font-medium text-sm">{{ session('message') }}</span>
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
                    <input 
                        wire:model="cpf" 
                        type="text" 
                        maxlength="14"
                        x-data="{
                            formatCpf() {
                                let value = $el.value.replace(/\D/g, ''); // Remove tudo que não for número
                                value = value.replace(/(\d{3})(\d)/, '$1.$2'); // Coloca o primeiro ponto
                                value = value.replace(/(\d{3})(\d)/, '$1.$2'); // Coloca o segundo ponto
                                value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2'); // Coloca o traço
                                $el.value = value;
                            }
                        }"
                        x-on:input="formatCpf()"
                        placeholder="000.000.000-00" 
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                    >
                    <p class="text-xs text-gray-500 mt-1">Digite apenas os números, o sistema formata automaticamente.</p>
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

            <div class="flex items-center justify-start gap-3 pt-6 mt-6 border-t border-gray-200">
                <button type="submit" class="px-5 py-2 bg-blue-600 border border-transparent rounded-lg text-sm font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                    Salvar alterações
                </button>
                <a href="{{ route('pacientes.index') }}" class="px-5 py-2 bg-white border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none transition-colors">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>