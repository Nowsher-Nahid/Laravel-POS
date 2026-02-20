<?php

namespace App\Livewire\Inventories;

use App\Models\Inventory;
use App\Models\Item;
use Dom\Text;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;

class ListInventories extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithTable;
    use InteractsWithSchemas;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Inventory::query())
            ->columns([
                TextColumn::make('item.name')
                    ->label('Item Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('quantity')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state < 10 ? 'danger' : 'success'),

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
                Action::make('create_inventory')
                    ->label('Add New Inventory')
                    ->icon('heroicon-m-plus-circle')
                    ->modalHeading('Create a New Inventory')
                    ->modalWidth('md')
                    ->modalDescription('Fill in the details below to register a new inventory.')
                    ->form([
                        Select::make('item_id')
                            ->label('Item')
                            ->relationship('item', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        
                        TextInput::make('quantity')
                            ->label('Quantity')
                            ->required()
                            ->numeric(),
                    ])
                    ->modalFooterActionsAlignment('end')
                    ->action(function (array $data, Action $action) {
                        Inventory::create($data);

                        Notification::make()
                            ->title('Inventory created successfully')
                            ->success()
                            ->send();

                        $action->modal()->close();
                    })
            ])
            ->recordActions([
                Action::make('edit')
                    ->url(fn (Inventory $record): string => route('inventories.edit', $record))
                    ->icon('heroicon-m-pencil-square')
                    ->iconButton(),

                Action::make('delete')
                    ->requiresConfirmation()
                    ->color('danger')
                    ->icon('heroicon-m-trash')
                    ->iconButton()
                    ->action(fn (Inventory $record) => $record->delete())
                    ->successNotificationTitle('Inventory deleted successfully'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('delete')
                        ->requiresConfirmation()
                        ->color('danger')
                        ->icon('heroicon-m-trash')
                        ->action(fn (Collection $records) => $records->each->delete())
                        ->successNotificationTitle('Selected inventories deleted successfully'),
                ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.inventories.list-inventories');
    }
}
