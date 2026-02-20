<?php

namespace App\Livewire\Categories;

use App\Models\Category;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

class EditCategory extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public Category $record;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->record->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Edit Category')
                    ->description('Update the details of the selected category.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Category Name')
                            ->required()
                            ->maxLength(255)
                            ->live(debounce: 500)
                            // ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) =>
                                $set('slug', Str::slug($state))
                            ),

                        TextInput::make('slug')
                            ->label('Category Slug')
                            ->disabled()
                            ->dehydrated()
                            ->maxLength(255),

                        Select::make('parent_id')
                            ->label('Parent Category')
                            ->relationship('parent', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        
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
            ->title('Category updated successfully')
            ->success()
            ->send();
    }

    public function render(): View
    {
        return view('livewire.categories.edit-category');
    }
}
