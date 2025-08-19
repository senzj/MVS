<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'customer_id', // Foreign key to customers table
        'user_id', // Foreign key to users table (staff who created the order)
        'delivery_id', // Foreign key to employees table (delivery person)
        'payment_type', // Payment method (cash, gcash)
        'status', // Order status (pending, delivered, completed, cancelled)
        'is_paid', // Whether the order is paid
        'receipt_number', // Unique receipt identifier
    ];

    /**
     * Get the customer associated with this order
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get the staff member who created this order
     */
    public function staff()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the delivery person assigned to this order
     */
    public function deliveryBoy()
    {
        return $this->belongsTo(Employee::class, 'delivery_id');
    }

    /**
     * Get the order items associated with this order
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    /**
     * Calculate the total amount for this order
     */
    public function getTotalAmountAttribute()
    {
        return $this->orderItems->sum(function ($item) {
            return $item->quantity * $item->price;
        });
    }

    /**
     * Get formatted status with color coding
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'pending' => 'yellow',
            'paid' => 'blue',
            'delivered' => 'purple',
            'completed' => 'green',
            'cancelled' => 'red',
            default => 'gray'
        };
    }
}
