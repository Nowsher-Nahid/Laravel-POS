<x-layouts::app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">

        <div>
            @livewire(\App\Livewire\ApplicationStats::class)
        </div>
        <div>
            @livewire(\App\Livewire\LatestSales::class)
        </div>
        
    </div>
</x-layouts::app>
