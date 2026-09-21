<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Estado de Consultorios</x-slot>

        {{-- Estilos para nuestro interruptor personalizado. Son los mismos que usa Filament. --}}
        <style>
            .custom-toggle:checked {
                background-color: #16a34a; /* Color verde de Filament */
                border-color: #16a34a;
            }
            .custom-toggle:checked + .dot {
                transform: translateX(100%);
            }
        </style>

        <div class="space-y-4">
            @foreach ($this->clinics as $index => $clinic)
                <div class="flex items-center justify-between p-3 bg-white rounded-lg border dark:bg-gray-800 dark:border-gray-700">
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white">{{ $clinic['name'] }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Pacientes en cola: {{ $clinic['queue_count'] }}</div>
                    </div>

                    <div wire:loading.class="opacity-50" wire:target="clinics.{{ $index }}.is_active">
                        {{-- --- ¡LA SOLUCIÓN ESTÁ AQUÍ! --- --}}
                        {{-- Reemplazamos <x-filament::toggle> por un interruptor HTML personalizado --}}
                        <label for="clinic-toggle-{{ $clinic['id'] }}" class="relative inline-flex items-center cursor-pointer">
                            <input
                                type="checkbox"
                                id="clinic-toggle-{{ $clinic['id'] }}"
                                class="sr-only peer"
                                wire:model.live="clinics.{{ $index }}.is_active"
                            >
                            <div class="w-11 h-6 bg-gray-200 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
                        </label>
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>