<?php

namespace App\Livewire\Sales;

use App\Models\Sale;
use Dom\Text;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;


class ListSales extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithTable;
    use InteractsWithSchemas;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Sale::query()->with(['saleItems.item', 'customer', 'paymentMethod']))
            ->columns([
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sale_details') // Unique name for the custom column
                    ->label('Sold Items')
                    ->bulleted()
                    ->getStateUsing(function (Sale $record) {
                        // Map through the related sale items to create the "Name x Quantity" strings
                        return $record->saleItems->map(function ($saleItem) {
                            $itemName = $saleItem->item->name ?? 'Unknown Item';
                            $quantity = $saleItem->quantity ?? 0;
                            
                            return "{$itemName} x {$quantity}";
                        });
                    })
                    ->limitList(2)
                    ->expandableLimitedList(),

                

                TextColumn::make('total_amount')
                    ->sortable()
                    ->money()
                    ->summarize(Sum::make()
                        ->label('Total Sales')
                        ->money()
                    ),

                TextColumn::make('discount')
                    ->money(),

                TextColumn::make('paid_amount')
                    ->money(),

                TextColumn::make('paymentMethod.name')
                    ->label('Payment Method')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                Action::make('view')
                    ->label('View')
                    // ->url(fn (Sale $record) => route('sales.show', $record))
                    ->openUrlInNewTab(),

                Action::make('delete')
                    ->requiresConfirmation()
                    ->color('danger')
                    ->label('Delete')
                    ->action(fn (Sale $record) => $record->delete())
                    ->successNotificationTitle('Sale deleted successfully'),
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
                        ->successNotificationTitle('Selected sales deleted successfully'),
                ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.sales.list-sales');
    }
}
