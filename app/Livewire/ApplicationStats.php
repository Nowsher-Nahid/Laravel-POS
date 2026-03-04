<?php

namespace App\Livewire;

use App\Models\Item;
use App\Models\Sale;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ApplicationStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Number of Items', Item::count()),
            Stat::make('Number of Users', User::count()),
            Stat::make('Number of Sales', Sale::count()),
        ];
    }
}
