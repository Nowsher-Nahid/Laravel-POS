<?php

use App\Livewire\Categories\CreateCategory;
use App\Livewire\Categories\EditCategory;
use App\Livewire\Categories\ListCategories;
use App\Livewire\Customers\ListCustomers;
use App\Livewire\Inventories\EditInventory;
use App\Livewire\Inventories\ListInventories;
use App\Livewire\Items\EditItem;
use App\Livewire\Items\ListItems;
use App\Livewire\Management\ListPaymentMethods;
use App\Livewire\Management\ListUsers;
use App\Livewire\Sales\ListSales;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/manage-users', ListUsers::class)->name('users.index');

    Route::get('/manage-categories', ListCategories::class)->name('categories.index');
    // Route::get('/create-category', CreateCategory::class)->name('categories.create');
    Route::get('/edit-category/{record}', EditCategory::class)->name('categories.edit');

    Route::get('/manage-items', ListItems::class)->name('items.index');
    Route::get('/edit-item/{record}', EditItem::class)->name('items.edit');

    Route::get('/manage-inventories', ListInventories::class)->name('inventories.index');
    Route::get('/edit-inventory/{record}', EditInventory::class)->name('inventories.edit');

    Route::get('/manage-payment-methods', ListPaymentMethods::class)->name('payment-methods.index');
    Route::get('/manage-sales', ListSales::class)->name('sales.index');
    Route::get('/manage-customers', ListCustomers::class)->name('customers.index');
});

require __DIR__.'/settings.php';
