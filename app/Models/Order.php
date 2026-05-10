<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Builder
 */
class Order extends Model
{
    protected $fillable = [
        'created_by',
        'customer_id',
        'delivered_by',
        'order_type',
        'order_total',
        'payment_type',
        'payment_status',   // 'unpaid' | 'paid' | 'refunded'
        'status',
        'receipt_number',
        'proof_of_payment',
        'amount_received',  // cash only, walk-in
        'change_amount',    // cash only, walk-in
    ];

    protected $casts = [
        'order_total'    => 'float',
        'amount_received'=> 'float',
        'change_amount'  => 'float',
    ];

    // ── Relationships ──────────────────────────────────────────────

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'delivered_by');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    // ── Computed attributes ────────────────────────────────────────

    public function getCalculatedTotalAttribute(): float
    {
        return (float) $this->orderItems->sum(fn ($i) => $i->quantity * $i->unit_price);
    }

    /**
     * Tailwind color name for the order status badge.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending'    => 'amber',
            'preparing'  => 'yellow',
            'in_transit' => 'indigo',
            'delivered'  => 'purple',
            'completed'  => 'green',
            'cancelled'  => 'red',
            default      => 'gray',
        };
    }

    /**
     * Tailwind color name for the payment_status badge.
     */
    public function getPaymentStatusColorAttribute(): string
    {
        return match ($this->payment_status) {
            'paid'     => 'green',
            'unpaid'   => 'red',
            'refunded' => 'purple',
            default    => 'gray',
        };
    }

    /**
     * Human-readable payment status label (translatable).
     */
    public function getPaymentStatusLabelAttribute(): string
    {
        return match ($this->payment_status) {
            'paid'     => __('Paid'),
            'unpaid'   => __('Unpaid'),
            'refunded' => __('Refunded'),
            default    => ucfirst($this->payment_status ?? ''),
        };
    }

    /**
     * Whether a proof-of-payment image has been stored.
     */
    public function getHasProofAttribute(): bool
    {
        return ! empty($this->proof_of_payment);
    }

    /**
     * Public URL for the proof-of-payment image (storage disk).
     */
    public function getProofUrlAttribute(): ?string
    {
        return $this->proof_of_payment
            ? asset('storage/' . $this->proof_of_payment)
            : null;
    }
}
