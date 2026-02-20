<?php

namespace App\Livewire\Items;

use App\Models\Item;
use Dom\Text;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Component;

class ListItems extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithTable;
    use InteractsWithSchemas;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Item::query())
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sku')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('barcode')
                    ->toggleable(isToggledHiddenByDefault: false),

                ImageColumn::make('image')
                    ->circular()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('cost_price')
                    ->money('usd')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('selling_price')
                    ->money('usd')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('unit')
                    ->searchable(),

                TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Inactive')
                    ->color(fn ($state) => $state ? 'success' : 'danger')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Action::make('create_item')
                    ->label('Add New Item')
                    ->icon('heroicon-m-plus-circle')
                    ->modalHeading('Create a New Item')
                    ->modalDescription('Fill in the details below to register a new item.')
                    ->form([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Item Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(fn ($state, callable $set) =>
                                        $set('slug', Str::slug($state))
                                    ),

                                TextInput::make('slug')
                                    ->label('Item Slug')
                                    ->disabled()
                                    ->dehydrated()
                                    ->maxLength(255)
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(fn ($state, callable $set) =>
                                        $set('slug', Str::slug($state))
                                    ),

                                Select::make('category_id')
                                    ->label('Category')
                                    ->relationship('category', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),

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
                                
                                TextInput::make('cost_price')
                                    ->numeric()
                                    ->prefix('$'),
                                    
                                TextInput::make('selling_price')
                                    ->numeric()
                                    ->prefix('$'),

                                FileUpload::make('image')
                                    ->label('Item Image')
                                    ->image()
                                    ->directory('items') //http://yourapp.test/app/private/storage/items/filename.jpg
                                    ->imagePreviewHeight('150')
                                    ->maxSize(2048)
                                    ->columnSpan('full'),

                                Toggle::make('is_active')
                                    ->label('Status')
                                    ->default(true)
                                    ->live()
                                    ->helperText(fn ($state) => $state ? 'Active' : 'Inactive'),
                            ]),
                    ])
                    ->action(function (array $data, Action $action) {
                        Item::create($data);

                        Notification::make()
                            ->title('Item created successfully')
                            ->success()
                            ->send();

                        $action->modal()->close();
                    })
                    ->modalFooterActionsAlignment('end'),
            ])
            ->recordActions([
                Action::make('edit')
                    ->url(fn (Item $record): string => route('items.edit', $record))
                    ->icon('heroicon-m-pencil-square')
                    ->iconButton(),

                Action::make('delete')
                    ->requiresConfirmation()
                    ->color('danger')
                    ->icon('heroicon-m-trash')
                    ->iconButton()
                    ->action(fn (Item $record) => $record->delete())
                    ->successNotificationTitle('Item deleted successfully'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([     
                    BulkAction::make('delete')
                        ->requiresConfirmation()
                        ->color('danger')
                        ->icon('heroicon-m-trash')
                        ->action(function (Collection $records) {
                            foreach ($records as $record) {
                                $record->delete();
                            }
                        })
                        ->successNotificationTitle('Selected items deleted successfully'),
                ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.items.list-items');
    }
}
