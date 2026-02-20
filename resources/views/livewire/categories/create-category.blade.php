<div>
    <form wire:submit="create">
        {{ $this->form }}

        <x-filament::button type="submit" class="mt-4" wire:target="create">
            Submit
        </x-filament::button>
    </form>

    <x-filament-actions::modals />
</div>
