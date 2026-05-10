<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryMovement extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
        'type',
        'quantity',
        'before_stocks',
        'before_sold',
        'after_stocks',
        'after_sold',
        'reference_type',
        'reference_id',
        'remarks',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'before_stocks' => 'integer',
        'before_sold' => 'integer',
        'after_stocks' => 'integer',
        'after_sold' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
