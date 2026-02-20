<?php

namespace App\Livewire\Categories;

use App\Models\Category;
use Dom\Text;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Component;

class ListCategories extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithTable;
    use InteractsWithSchemas;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Category::query())
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('parent.name')
                    ->label('Parent Category'),

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
                Action::make('create_category')
                    ->label('Add New Category')
                    ->icon('heroicon-m-plus-circle')
                    ->modalHeading('Create a New Category') // This is the title at the top of the modal
                    ->modalDescription('Fill in the details below to register a new category.')
                    ->modalWidth('md')
                    ->form([
                        TextInput::make('name')
                            ->label('Category Name')
                            ->required()
                            ->maxLength(255)
                            ->live(debounce: 500)
                            ->afterStateUpdated(fn ($state, callable $set) =>
                                $set('slug', Str::slug($state))
                            ),
                        
                        Select::make('parent_id')
                            ->label('Parent Category')
                            ->relationship('parent', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->nullable(),

                        TextInput::make('slug')
                            ->label('Category Slug')
                            ->disabled()
                            ->dehydrated()
                            ->maxLength(255),

                        Toggle::make('is_active')
                            ->label('Status')
                            ->default(true)
                            ->live()
                            ->helperText(fn ($state) => $state ? 'Active' : 'Inactive'),
                    ])
                    ->action(function (array $data, Action $action) {
                        Category::create($data);

                        Notification::make()
                            ->title('Category created successfully')
                            ->success()
                            ->send();

                        $action->modal()->close();
                    })
                    ->modalFooterActionsAlignment('end'),
            ])
            ->recordActions([
                Action::make('edit')
                    ->url(fn (Category $record): string => route('categories.edit', $record))
                    ->icon('heroicon-m-pencil-square')
                    ->iconButton(),

                Action::make('delete')
                    ->requiresConfirmation()
                    ->color('danger')
                    ->icon('heroicon-m-trash')
                    ->iconButton()
                    ->action(function (Category $record) {

                        // Prevent deletion if category has items
                        if ($record->items()->exists()) {
                            Notification::make()
                                ->title('Cannot delete category')
                                ->body('This category has associated items.')
                                ->danger()
                                ->send();

                            return;
                        }

                        // Prevent deletion if category has children
                        if ($record->children()->exists()) {
                            Notification::make()
                                ->title('Cannot delete category')
                                ->body('This category has subcategories.')
                                ->danger()
                                ->send();

                            return;
                        }

                        // Safe to delete
                        $record->delete();

                        Notification::make()
                            ->title('Category deleted successfully')
                            ->success()
                            ->send();
                    })
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
                        ->successNotificationTitle('Selected categories deleted successfully'),
                ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.categories.list-categories');
    }
}
