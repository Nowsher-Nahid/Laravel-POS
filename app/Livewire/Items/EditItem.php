<?php

namespace App\Livewire\Items;

use App\Models\Item;
use Dom\Text;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

class EditItem extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public Item $record;

    public ?array $data = [];

    public function mount(): void
    {
        //Populate the form with the record's data
        $this->form->fill($this->record->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Edit Item')
                    ->description('Update the details of the selected item.')
                    ->schema([
                        
                        Grid::make(3)->schema([
                            TextInput::make('name')
                                ->label('Item Name')
                                ->required()
                                ->maxLength(255)
                                ->live(debounce: 500)
                                ->afterStateUpdated(fn ($state, callable $set) =>
                                    $set('slug', Str::slug($state))
                                ),
                            
                            Select::make('category_id')
                                ->label('Item Category')
                                ->relationship('category', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),

                            TextInput::make('slug')
                                ->disabled()
                                ->dehydrated()
                                ->maxLength(255),
                        ]),

                        Grid::make(3)->schema([
                        
                            TextInput::make('sku')
                                ->label('SKU')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(100),
                            
                            TextInput::make('barcode')
                                ->label('Barcode')
                                ->maxLength(100),

                            Select::make('unit')
                                ->label('Unit')
                                ->options([
                                    'pcs' => 'Pieces',
                                    'kg'  => 'Kilogram',
                                    'ltr' => 'Liter',
                                ])
                                ->required(),
                        ]),

                        Grid::make(3)->schema([
                            TextInput::make('cost_price')
                                ->numeric()
                                ->prefix('$'),
                            TextInput::make('selling_price')
                                ->numeric()
                                ->prefix('$'),
                            FileUpload::make('image')
                                ->label('Item Image')
                                ->image()
                                ->directory('items') //http://yourapp.test/storage/items/filename.jpg
                                ->imagePreviewHeight('150')
                                ->maxSize(2048),
                        ]),

                        Toggle::make('is_active')
                            ->label('Status')
                            ->live()
                            ->helperText(fn ($state) => $state ? 'Active' : 'Inactive'),
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
            ->title('Item updated successfully')
            ->success()
            ->send();
    }

    public function render(): View
    {
        return view('livewire.items.edit-item');
    }
}
