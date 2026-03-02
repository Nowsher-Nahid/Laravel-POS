<?php

namespace App\Livewire;

use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Item;
use App\Models\PaymentMethod;
use Filament\Notifications\Notification;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Pos extends Component
{
    public $items;
    public $customers;
    public $paymentMethods;
    public $search = '';
    public $cart = [];
    // public $cart = [
    //     [
    //         'id' => 1,
    //         'name' => 'Sample Item',
    //         'quantity' => 2,
    //         'price' => 19.99,
    //     ],
    //     [
    //         'id' => 2,
    //         'name' => 'Another Item',
    //         'quantity' => 10,
    //         'price' => 9.99,
    //     ],
    //     [
    //         'id' => 3,
    //         'name' => 'Third Item',
    //         'quantity' => 3,
    //         'price' => 5.99,
    //     ],
    // ];

    // checkout properties
    public $customer_id = null;
    public $payment_method_id = null;
    public $paid_amount = 0;
    public $discount_amount = 0; //Flat amount, not %

    public function mount()
    {
        $this->items = Item::with('inventory')
                            ->where('is_active', true)
                            ->whereHas('inventory', function ($query) {
                                $query->where('quantity', '>', 0);
                            })
                            ->get();
        $this->customers = Customer::all();
        $this->paymentMethods = PaymentMethod::all();
    }


    // CART FUNCTIONS STARTS --------------------------------------------------------------------------

    // This function adds an item to the shopping cart.
    // It handles: =>

    // 1. Checking if the item exists
    // 2. Checking inventory
    // 3. Updating the cart quantity if item already exists
    // 4. Adding a new item if it doesn’t exist
    // 5. Updating inventory
    // 6. Sending notifications

    public function addToCart($itemId) {

        $item = $this->items->firstWhere('id', $itemId);

        // If the item is not found, show notification and return
        if(!$item) {
            Notification::make()
                ->title('Item Not Found')
                ->body('The selected item could not be found.')
                ->danger()
                ->send();
            return;
        }

        // If inventory does not have enough quantity, show notification and return
        if($item->inventory->quantity <= 0) {
            Notification::make()
                ->title('Out of Stock')
                ->body('This item is currently out of stock and cannot be added to the cart.')
                ->danger()
                ->send();
            return;
        }

        // // If item is already in cart, update quantity. (Filter the cart to find the item by ID and match it with the provided itemId)
        // $cartItem = array_filter($this->cart, function ($cartItem) use ($itemId) {
        //     return $cartItem['id'] == $itemId;
        // });

        // // If item is found in cart, increment quantity
        // if (!empty($cartItem)) {
        //     $key = key($cartItem);
        //     $this->cart[$key]['quantity'] += 1;
        // } else {
        //     // If item is not in cart, add it
        //     $this->cart[] = [
        //         'id' => $item->id,
        //         'name' => $item->name,
        //         'quantity' => 1,
        //         'price' => $item->selling_price,
        //     ];
        // }

        // Use Laravel Collection to check if item is in cart
        $cartIndex = collect($this->cart)->search(fn($cartItem) => $cartItem['id'] == $itemId);

        if ($cartIndex !== false) {
            // Increment quantity if already in cart
            $this->cart[$cartIndex]['quantity']++;
        } else {
            // Add new item to cart
            $this->cart[] = [
                'id' => $item->id,
                'name' => $item->name,
                'quantity' => 1,
                'price' => $item->selling_price,
            ];
        }
        
        // Decrease inventory quantity
        $item->inventory->decrement('quantity', 1);

        // Show success notification
        Notification::make()
            ->title('Added to Cart')
            ->body("{$item->name} has been added to the cart.")
            ->success()
            ->send();
    }

    //Remove item from cart
    public function removeFromCart($index) {
        if (isset($this->cart[$index])) {
            $cartItem = $this->cart[$index];
            
            // Increase inventory quantity back            
            $item = Item::find($cartItem['id']);
            if ($item) {
                $item->inventory->increment('quantity', $cartItem['quantity']);
            }

            // Remove item from cart
            unset($this->cart[$index]);
            $this->cart = array_values($this->cart); // Reindex the cart array 
            // After updating quantity
            $this->cart = $this->cart;

            // Show success notification
            Notification::make()
                ->title('Removed from Cart')
                ->body("{$cartItem['name']} has been removed from the cart.")
                ->success()
                ->send();
        }
    }

    // Update quantity of an item in the cart
    public function updateQuantity($index, $quantity) {
        $quantity = max(1, (int)$quantity); // Ensure quantity is at least 1

        if (isset($this->cart[$index])) {
            $cartItem = $this->cart[$index];
            $item = Item::find($cartItem['id']);
            if ($item) {
                $currentCartQuantity = $cartItem['quantity'];
                $quantityChange = $quantity - $currentCartQuantity;

                // Check if inventory can accommodate the quantity change
                if ($quantityChange > 0 && $item->inventory->quantity < $quantityChange) {
                    // Update cart quantity
                    $this->cart = $this->cart;
                    Notification::make()
                        ->title('Insufficient Stock')
                        ->body("Cannot update quantity. Only {$item->inventory->quantity} units available in stock.")
                        ->danger()
                        ->send();
                    return;
                }

                // Update inventory based on quantity change
                if ($quantityChange > 0) {
                    $item->inventory->decrement('quantity', $quantityChange);
                } elseif ($quantityChange < 0) {
                    $item->inventory->increment('quantity', abs($quantityChange));
                }

                // Update cart quantity
                $this->cart[$index]['quantity'] = $quantity;

                // After updating quantity
                $this->cart = $this->cart;

                // Show success notification
                Notification::make()
                    ->title('Cart Updated')
                    ->body("Quantity for {$cartItem['name']} has been updated to {$quantity}.")
                    ->success()
                    ->send();
            }
        }
    }

    // CART FUNCTIONS ENDS --------------------------------------------------------------------------

    #[Computed]
    public function filteredItems() {

        if (empty($this->search)) {
            return $this->items;
        }

        return $this->items->filter(function ($item) {
            return str_contains(strtolower($item->name), strtolower($this->search)) ||
                   str_contains(strtolower($item->sku), strtolower($this->search));
        });
    }

    #[Computed]
    public function subTotal() {        
        return collect($this->cart)->sum(function ($cartItem) {
            return $cartItem['quantity'] * $cartItem['price'];
        });
    }

    #[Computed]
    public function tax() {
        return $this->subTotal * 0.15; // Assuming a fixed tax rate of 15%
    }

    #[Computed]
    public function totalBeforeDiscount() {
        return $this->subTotal + $this->tax;
    }

    #[Computed]
    public function total() {
        return max(0, $this->totalBeforeDiscount - $this->discount_amount);
    }

    #[Computed]
    public function change() {
        if ($this->paid_amount < $this->total) {
            return 0;
        }
        return max(0, $this->paid_amount - $this->total);
    }
    
    public function render()
    {
        return view('livewire.pos');
    }
}
