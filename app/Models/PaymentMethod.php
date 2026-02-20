<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentMethodFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    // One payment method can be used by many sales
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}
