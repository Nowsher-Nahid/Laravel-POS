<?php

namespace App\Livewire\Inventories;

use App\Models\Inventory;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Livewire\Component;

class EditInventory extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public Inventory $record;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->record->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Edit Inventory')
                    ->description('Update the details of the selected inventory.')
                    ->columns(2)
                    ->schema([
                        Select::make('item_id')
                            ->label('Item')
                            ->relationship('item', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->unique(ignorable: $this->record),

                        TextInput::make('quantity')
                            ->label('Quantity')
                            ->required()
                            ->numeric()
                            ->minValue(0),
                ])
            ])
            ->statePath('data')
            ->model($this->record);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $this->record->update($data);

        Notification::make()
            ->title('Inventory updated successfully')
            ->success()
            ->send();
    }

    public function render(): View
    {
        return view('livewire.inventories.edit-inventory');
    }
}
