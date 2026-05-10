<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'refunded_quantity',  // tracks how many units have been returned
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'quantity'          => 'integer',
        'refunded_quantity' => 'integer',
        'unit_price'        => 'float',
        'total_price'       => 'float',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Units that can still be refunded in a future refund.
     */
    public function getReturnableAttribute(): int
    {
        return max(0, $this->quantity - ($this->refunded_quantity ?? 0));
    }

    /**
     * Whether this line item has been fully returned.
     */
    public function getIsFullyRefundedAttribute(): bool
    {
        return ($this->refunded_quantity ?? 0) >= $this->quantity;
    }
}
