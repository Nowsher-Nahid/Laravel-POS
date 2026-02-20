<?php

namespace App\Models;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    /** @use HasFactory<\Database\Factories\CategoryFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'is_active',
    ];

    // One category has many items
    public function items()
    {
        return $this->hasMany(Item::class);
    }

    // Parent category
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // Children category
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }
}
