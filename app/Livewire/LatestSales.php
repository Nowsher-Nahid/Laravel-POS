<?php

namespace App\Livewire;

use App\Models\Sale;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestSales extends TableWidget
{
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
                //
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}
