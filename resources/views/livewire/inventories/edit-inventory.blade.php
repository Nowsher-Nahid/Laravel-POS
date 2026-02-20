<div>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="flex justify-end">
            <x-filament::button type="submit" class="mt-4" wire:target="save">
                Update Inventory
            </x-filament::button>
        </div>

    </form>

    <x-filament-actions::modals />
</div>
