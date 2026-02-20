<?php

namespace App\Livewire\Categories;

use App\Models\Category;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use Livewire\Component;

class CreateCategory extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Create Category')
                    ->description('Create a new category.')
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
                            ->default(true)
                            ->live()
                            ->helperText(fn ($state) => $state ? 'Active' : 'Inactive'),
                    ])
            ])
            ->statePath('data')
            ->model(Category::class);
    }

    public function create(): void
    {
        $data = $this->form->getState();

        $record = Category::create($data);

        $this->form->model($record)->saveRelationships();

        Notification::make()
            ->title('Category created successfully')
            ->success()
            ->send();
    }

    public function render(): View
    {
        return view('livewire.categories.create-category');
    }
}
