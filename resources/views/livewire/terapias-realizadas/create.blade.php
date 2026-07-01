<div>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('terapias-realizadas.index') }}" wire:navigate class="p-2 -ml-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-full transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            
            <div class="flex flex-col">
                <nav class="flex text-xs text-gray-500 mb-1" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1">
                        <li><a href="{{ route('terapias-realizadas.index') }}" wire:navigate class="hover:text-blue-600 transition-colors">Terapias Realizadas</a></li>
                        <li><span class="mx-1 text-gray-400">/</span></li>
                        <li aria-current="page" class="text-gray-700 font-medium">Registrar Consulta</li>
                    </ol>
                </nav>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Registrar Consulta</h2>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <form wire:submit.prevent="save" class="bg-white shadow-sm sm:rounded-lg border border-gray-200 p-6 space-y-6">
            
            @if (session()->has('message'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" 
                    class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg relative flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span class="font-medium text-sm">{{ session('message') }}</span>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Paciente <span class="text-red-500">*</span></label>
                    <select wire:model.live="patient_id" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Selecione o Paciente</option>
                        @foreach($patients as $patient)
                            <option value="{{ $patient->id }}">{{ $patient->name }}</option>
                        @endforeach
                    </select>
                    @error('patient_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Terapia <span class="text-red-500">*</span></label>
                    <select wire:model.live="therapy_id" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Selecione a Terapia</option>
                        @foreach($therapies as $therapy)
                            <option value="{{ $therapy->id }}">{{ $therapy->name }}</option>
                        @endforeach
                    </select>
                    @error('therapy_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Profissional <span class="text-red-500">*</span></label>
                    <select wire:model="professional_id" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" {{ empty($therapy_id) ? 'disabled' : '' }}>
                        <option value="">{{ empty($therapy_id) ? 'Selecione a Terapia primeiro' : 'Selecione o Profissional' }}</option>
                        @foreach($professionals as $professional)
                            <option value="{{ $professional->id }}">{{ $professional->name }}</option>
                        @endforeach
                    </select>
                    @error('professional_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Atendimento <span class="text-red-500">*</span></label>
                    <select wire:model="service_type_id" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Selecione o Tipo</option>
                        @foreach($serviceTypes as $serviceType)
                            <option value="{{ $serviceType->id }}">{{ $serviceType->name }}</option>
                        @endforeach
                    </select>
                    @error('service_type_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <hr class="border-gray-200">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50 p-4 rounded-xl border border-gray-100">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Atalho de Data</label>
                    <div class="inline-flex p-1 bg-gray-200 rounded-lg shadow-inner">
                        <button type="button" 
                            wire:click="$set('data_rapida', 'ontem')"
                            class="px-4 py-1.5 text-xs font-bold rounded-md transition-all {{ $data_rapida === 'ontem' ? 'bg-yellow-500 text-white shadow-sm' : 'text-gray-600 hover:text-gray-800' }}">
                            Ontem
                        </button>

                        <button type="button" 
                            wire:click="$set('data_rapida', 'hoje')"
                            class="px-4 py-1.5 text-xs font-bold rounded-md transition-all {{ $data_rapida === 'hoje' ? 'bg-green-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-800' }}">
                            Hoje
                        </button>

                        <button type="button" 
                            wire:click="$set('data_rapida', 'outro')"
                            class="px-4 py-1.5 text-xs font-bold rounded-md transition-all {{ $data_rapida === 'outro' ? 'bg-gray-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-800' }}">
                            Outra Data
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Data da Consulta <span class="text-red-500">*</span></label>
                    <input type="date" wire:model="appointment_date" 
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 {{ $data_rapida !== 'outro' ? 'bg-gray-100 cursor-not-allowed text-gray-500' : '' }}" 
                        {{ $data_rapida !== 'outro' ? 'readonly' : '' }}>
                    @error('appointment_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Check-in <span class="text-red-500">*</span></label>
                    <input type="time" wire:model.live.debounce.500ms="check_in" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('check_in') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Check-out <span class="text-red-500">*</span></label>
                    <input type="time" wire:model.live.debounce.500ms="check_out" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('check_out') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Qtd de Sessões</label>
                    <input type="number" wire:model="session_number" readonly class="block w-full border-gray-300 bg-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 cursor-not-allowed text-gray-600">
                    <p class="text-xs text-gray-500 mt-1">Calculado automaticamente</p>
                    @error('session_number') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="flex items-center justify-start gap-3 pt-6 border-t border-gray-200">
                <button type="submit" class="px-5 py-2 bg-blue-600 border border-transparent rounded-lg text-sm font-semibold text-white hover:bg-blue-700 flex items-center gap-2">
                    <span wire:loading.remove wire:target="save">Registrar Consulta</span>
                    <span wire:loading wire:target="save">Registrando...</span>
                </button>
                <button type="button" wire:click="saveAndCreateAnother" class="px-5 py-2 bg-white border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                    <span wire:loading.remove wire:target="saveAndCreateAnother">Salvar e criar outro</span>
                    <span wire:loading wire:target="saveAndCreateAnother">Registrando...</span>
                </button>
                <a href="{{ route('terapias-realizadas.index') }}" wire:navigate class="px-5 py-2 bg-white border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>