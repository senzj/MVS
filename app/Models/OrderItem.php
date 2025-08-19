<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', //fk to orders table
        'product_id', //fk to products table
        'quantity',
        'unit_price', // Add this - individual product price
        'total_price', // This stays - total for this line item
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    // relations
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    // Accessor to get unit price from product if not stored
    public function getUnitPriceAttribute($value)
    {
        return $value ?? $this->product?->price ?? 0;
    }
}
