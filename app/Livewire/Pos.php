<?php

namespace App\Livewire;

use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Item;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Pos extends Component
{
    public $items;
    public $customers;
    public $paymentMethods;
    public $search = '';
    public $cart = [];

    // checkout properties
    public $customer_id = null;
    public $payment_method_id = null;
    public $paid_amount = 0;
    public $discount_amount = 0; // Flat amount

    public function mount()
    {
        $this->items = Item::with('inventory')
            ->where('is_active', true)
            ->whereHas('inventory', fn ($q) => $q->where('quantity', '>', 0))
            ->get();

        $this->customers = Customer::all();
        $this->paymentMethods = PaymentMethod::all();
    }

    /*
    |--------------------------------------------------------------------------
    | 🔥 GLOBAL SWAL HELPER (CLEAN)
    |--------------------------------------------------------------------------
    */
    protected function swal($title, $message = '', $icon = 'success')
    {
        $this->dispatch('swal', [
            'title' => $title,
            'message' => $message,
            'icon' => $icon,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 🛒 ADD TO CART
    |--------------------------------------------------------------------------
    */
    public function addToCart($itemId)
    {
        $item = $this->items->firstWhere('id', $itemId);

        if (!$item) {
            $this->swal('Item Not Found', 'The selected item could not be found.', 'error');
            return;
        }

        if ($item->inventory->quantity <= 0) {
            $this->swal('Out of Stock', 'This item is currently out of stock.', 'error');
            return;
        }

        $cartIndex = collect($this->cart)->search(
            fn ($cartItem) => $cartItem['id'] == $itemId
        );

        if ($cartIndex !== false) {
            $this->cart[$cartIndex]['quantity']++;
        } else {
            $this->cart[] = [
                'id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku,
                'quantity' => 1,
                'price' => $item->selling_price,
            ];
        }

        // decrease stock
        $item->inventory->decrement('quantity', 1);

        $this->swal('Added!', "{$item->name} added to cart.", 'success');
    }

    /*
    |--------------------------------------------------------------------------
    | ❌ REMOVE FROM CART
    |--------------------------------------------------------------------------
    */
    public function removeFromCart($index)
    {
        if (!isset($this->cart[$index])) return;

        $cartItem = $this->cart[$index];
        $item = Item::find($cartItem['id']);

        if ($item) {
            $item->inventory->increment('quantity', $cartItem['quantity']);
        }

        unset($this->cart[$index]);
        $this->cart = array_values($this->cart);

        $this->swal('Removed!', "{$cartItem['name']} removed from cart.", 'success');
    }

    /*
    |--------------------------------------------------------------------------
    | 🔄 UPDATE QUANTITY
    |--------------------------------------------------------------------------
    */
    public function updateQuantity($index, $quantity)
    {
        $quantity = max(1, (int) $quantity);

        if (!isset($this->cart[$index])) return;

        $cartItem = $this->cart[$index];
        $item = Item::find($cartItem['id']);

        if (!$item) return;

        $currentCartQty = $cartItem['quantity'];
        $change = $quantity - $currentCartQty;

        // check stock
        if ($change > 0 && $item->inventory->quantity < $change) {
            $this->swal(
                'Insufficient Stock',
                "Only {$item->inventory->quantity} units available.",
                'warning'
            );
            return;
        }

        // adjust inventory
        if ($change > 0) {
            $item->inventory->decrement('quantity', $change);
        } elseif ($change < 0) {
            $item->inventory->increment('quantity', abs($change));
        }

        $this->cart[$index]['quantity'] = $quantity;

        $this->swal(
            'Cart Updated',
            "{$cartItem['name']} quantity updated to {$quantity}.",
            'success'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 💳 CHECKOUT
    |--------------------------------------------------------------------------
    */
    public function checkout()
    {
        if (empty($this->cart)) {
            $this->swal('Cart is Empty!', 'Please add items first.', 'warning');
            return;
        }

        if ($this->paid_amount < $this->total) {
            $this->swal(
                'Insufficient Payment!',
                'Paid amount is less than total.',
                'warning'
            );
            return;
        }

        try {
            DB::beginTransaction();

            $sale = Sale::create([
                'customer_id' => $this->customer_id,
                'payment_method_id' => $this->payment_method_id,
                'total_amount' => $this->total,
                'paid_amount' => $this->paid_amount,
                'discount' => $this->discount_amount,
            ]);

            foreach($this->cart as $cartItem) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'item_id' => $cartItem['id'],
                    'quantity' => $cartItem['quantity'],
                    'price' => $cartItem['price'],
                ]);

                // Update the stock
                Inventory::where('item_id', $cartItem['id'])
                    ->decrement('quantity', $cartItem['quantity']);
            }

            DB::commit();

            // reset POS
            $this->cart = [];
            $this->search = '';
            $this->customer_id = null;
            $this->payment_method_id = null;
            $this->paid_amount = 0;
            $this->discount_amount = 0;

            // ✅ success example
            $this->swal('Success!', 'Sale completed successfully.', 'success');
        } catch (\Throwable $e) {
            DB::rollBack();

            $this->swal(
                'Checkout Failed!',
                $e->getMessage(), // 👈 shows real error
                'error'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 🔍 FILTERED ITEMS
    |--------------------------------------------------------------------------
    */
    #[Computed]
    public function filteredItems()
    {
        if (empty($this->search)) return $this->items;

        return $this->items->filter(fn ($item) =>
            str_contains(strtolower($item->name), strtolower($this->search)) ||
            str_contains(strtolower($item->sku), strtolower($this->search))
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 💰 TOTALS
    |--------------------------------------------------------------------------
    */
    #[Computed]
    public function subTotal()
    {
        return collect($this->cart)->sum(
            fn ($item) => $item['quantity'] * $item['price']
        );
    }

    #[Computed]
    public function tax()
    {
        return $this->subTotal * 0.15;
    }

    #[Computed]
    public function totalBeforeDiscount()
    {
        return $this->subTotal + $this->tax;
    }

    #[Computed]
    public function total()
    {
        return max(0, $this->totalBeforeDiscount - $this->discount_amount);
    }

    #[Computed]
    public function change()
    {
        if ($this->paid_amount < $this->total) return 0;
        return max(0, $this->paid_amount - $this->total);
    }

    public function render()
    {
        return view('livewire.pos');
    }
}