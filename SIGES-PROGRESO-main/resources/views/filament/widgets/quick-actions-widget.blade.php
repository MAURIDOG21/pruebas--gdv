<x-filament::section>
    <x-slot name="heading">Acciones rápidas</x-slot>
    <div class="flex items-end gap-2">
        <div>
            <label class="text-sm font-medium">Ticket</label>
            <input type="text" wire:model.defer="ticket_number" placeholder="Ej: LOCAL-2025-0001" class="fi-input w-64">
        </div>
        <x-filament::button wire:click="findTicket" color="primary">
            Buscar
        </x-filament::button>
    </div>
</x-filament::section>
