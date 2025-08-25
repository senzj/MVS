<?php

namespace App\Models;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'created_by', // person who created this order
        'customer_id', // Foreign key to customers table
        'delivered_by', // Foreign key to employees table (delivery person)
        'order_type', // Type of order (walk_in, deliver)
        'order_total', // Total amount for the order
        'payment_type', // Payment method (cash, gcash)
        'status', // Order status (pending, delivered, completed, cancelled)
        'is_paid', // Whether the order is paid or not
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
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the delivery person assigned to this order
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'delivered_by');
    }

    /**
     * Get the order items associated with this order
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    /**
     * Calculate the total amount from order items (for verification)
     */
    public function getCalculatedTotalAttribute()
    {
        return $this->orderItems->sum(function ($item) {
            return $item->quantity * $item->unit_price;
        });
    }

    /**
     * Get formatted status with color coding
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'pending'    => 'amber',   // waiting, attention (yellowish-orange, softer than bright yellow)
            'preparing'  => 'yellow',  // batch preparation phase
            'paid'       => 'blue',    // financial / confirmed
            'in_transit' => 'indigo',  // movement / ongoing process
            'delivered'  => 'purple',  // finished delivery, but not fully closed
            'completed'  => 'green',   // success / done
            'cancelled'  => 'red',     // error / stop
            default      => 'gray',    // unknown / neutral
        };
    }
}
