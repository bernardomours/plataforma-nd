<div>
    <x-slot name="header">
        <div class="flex flex-col">
            
            <nav class="flex text-xs text-gray-500 mb-2 ml-[44px]" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1">
                    <li>
                        <a href="{{ route('pacientes.index') }}" wire:navigate class="hover:text-blue-600 transition-colors">Pacientes</a>
                    </li>
                    <li><span class="mx-1 text-gray-400">/</span></li>
                    <li aria-current="page" class="text-gray-700 font-medium">Controle de Carga Horária</li>
                </ol>
            </nav>

            <div class="flex items-center gap-3">
                <a href="{{ route('pacientes.index') }}" wire:navigate class="p-2 -ml-2 text-gray-500 hover:text-blue-600 hover:bg-gray-200 rounded-full transition-colors focus:outline-none" title="Voltar para a lista de pacientes">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
                    Carga Horária
                </h2>
            </div>

        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        
        @if (session()->has('message'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" 
                 class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center gap-2">
                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span class="font-medium text-sm">{{ session('message') }}</span>
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden p-6">
            
            <div class="flex justify-between items-start mb-6 border-b pb-4">
                <div>
                    <h3 class="text-2xl font-bold text-gray-900 uppercase">{{ $patient->name }}</h3>
                    <p class="text-sm text-gray-500 mt-1">Solicitações de carga horária do paciente.</p>
                </div>
                <div>
                    <button wire:click="openModal" class="px-4 py-2 bg-blue-500 text-white rounded-md text-sm font-semibold hover:bg-blue-600 transition shadow-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Nova Solicitação
                    </button>
                </div>
            </div>

            <div class="mb-6 flex items-end space-x-3 bg-gray-50 p-4 rounded-lg border border-gray-100">
                <div class="flex-grow max-w-xs">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Filtrar por Mês/Ano</label>
                    <input type="month" wire:model.live="filter_month_year" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                <div>
                    <button wire:click="clearFilter" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md text-sm font-semibold hover:bg-gray-300 transition shadow-sm border border-gray-300">
                        Limpar
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto border border-gray-200 rounded-lg">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase tracking-wider text-gray-600 font-semibold">
                            <th class="py-3 px-4">Terapia</th>
                            <th class="py-3 px-4">Tipo Atendimento</th>
                            <th class="py-3 px-4">Mês/Ano</th>
                            <th class="py-3 px-4">Requisição</th>
                            <th class="py-3 px-4">CH Solicitada</th>
                            <th class="py-3 px-4">CH Liberada</th>
                            <th class="py-3 px-4">CH Planejada</th>
                            <th class="py-3 px-4 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm text-gray-800">
                        @forelse ($records as $record)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="py-3 px-4 font-bold">{{ $record->therapy?->name }}</td>
                                <td class="py-3 px-4">{{ $record->serviceType?->name }}</td>
                                <td class="py-3 px-4 capitalize">{{ \Carbon\Carbon::parse($record->month_year)->translatedFormat('F \d\e Y') }}</td>
                                <td class="py-3 px-4">{{ $record->requisition_number }}</td>
                                <td class="py-3 px-4">{{ number_format($record->requested_hours, 2, '.', '') }}</td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                        {{ number_format($record->approved_hours, 2, '.', '') }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ number_format($record->planned_hours, 2, '.', '') }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right space-x-2">
                                    <button wire:click="editRecord({{ $record->id }})" class="text-blue-600 hover:text-blue-800 font-medium text-sm transition-colors inline-flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                        Editar
                                    </button>
                                    <button wire:click="deleteRecord({{ $record->id }})" wire:confirm="Tem certeza que deseja excluir esta solicitação?" class="text-red-600 hover:text-red-800 font-medium text-sm transition-colors inline-flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        Excluir
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 text-center text-gray-500">
                                    Nenhuma solicitação de carga horária encontrada para este paciente.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($records->isNotEmpty())
                    <tfoot class="bg-gray-50 border-t border-gray-200 font-bold text-gray-700">
                        <tr>
                            <td colspan="4" class="py-3 px-4 text-right">Total:</td>
                            <td class="py-3 px-4">{{ number_format($totals['requested'], 2, '.', '') }}</td>
                            <td class="py-3 px-4">{{ number_format($totals['approved'], 2, '.', '') }}</td>
                            <td class="py-3 px-4">{{ number_format($totals['planned'], 2, '.', '') }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    @if($isModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-600 bg-opacity-50 transition-opacity backdrop-blur-sm" wire:click="closeModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <form wire:submit.prevent="saveRecord">
                        <div class="bg-white px-6 pt-6 pb-4">
                            <h3 class="text-lg leading-6 font-bold text-gray-900 mb-4 border-b pb-2" id="modal-title">
                                {{ $editingRecordId ? 'Editar Solicitação' : 'Nova Solicitação' }}
                            </h3>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Terapia <span class="text-red-500">*</span></label>
                                    <select wire:model="therapy_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        <option value="">Selecione</option>
                                        @foreach($therapies as $therapy)
                                            <option value="{{ $therapy->id }}">{{ $therapy->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('therapy_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Tipo de Atendimento <span class="text-red-500">*</span></label>
                                    <select wire:model="service_type_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        <option value="">Selecione</option>
                                        @foreach($serviceTypes as $type)
                                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('service_type_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Mês/Ano <span class="text-red-500">*</span></label>
                                    <input type="month" wire:model="month_year" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    @error('month_year') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Número da Requisição <span class="text-red-500">*</span></label>
                                    <input type="text" wire:model="requisition_number" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    @error('requisition_number') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">CH Solicitada <span class="text-red-500">*</span></label>
                                    <input type="number" step="0.01" wire:model="requested_hours" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    @error('requested_hours') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">CH Liberada</label>
                                    <input type="number" step="0.01" wire:model="approved_hours" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">CH Planejada</label>
                                    <input type="number" step="0.01" wire:model="planned_hours" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 px-6 py-4 sm:flex sm:flex-row-reverse rounded-b-xl">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                                Salvar
                            </button>
                            <button type="button" wire:click="closeModal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>