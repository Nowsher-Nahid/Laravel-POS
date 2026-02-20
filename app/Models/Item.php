<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    /** @use HasFactory<\Database\Factories\ItemFactory> */
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'sku',
        'barcode',
        'image',
        'cost_price',
        'selling_price',
        'unit',
        'is_active',
    ];

    // One caategory has many items
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // One item has one inventory
    public function inventory()
    {
        return $this->hasOne(Inventory::class);
    }

    // One item has many sale items -> one item can be sold multiple times
    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }
}
